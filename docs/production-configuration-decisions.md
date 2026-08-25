# Decisiones de configuracion para produccion

Este documento explica por que se tomaron las decisiones de configuracion usadas para desplegar PoTable en un VPS. La idea no es solo listar comandos, sino entender que hay que mirar cuando una aplicacion Laravel pasa de desarrollo a produccion.

La guia operativa corta esta en `docs/production-deploy.md`. Este archivo es la explicacion de fondo.

## Principio general

En desarrollo buscamos comodidad: montar el codigo local, ver errores completos, reiniciar facil, exponer puertos, usar datos demo.

En produccion buscamos otra cosa:

- Que solo se exponga lo necesario a internet.
- Que los errores internos no se muestren al usuario.
- Que los secretos no queden versionados.
- Que los procesos sean reiniciables y predecibles.
- Que HTTPS sea obligatorio.
- Que las sesiones y cookies viajen de forma segura.
- Que la base de datos quede aislada.
- Que el bot de Telegram reciba mensajes solo desde Telegram.

Por eso hay archivos separados para produccion en vez de reutilizar directamente el `docker-compose.yml` local.

## Separar desarrollo de produccion

El proyecto mantiene dos mundos:

- `docker-compose.yml`: pensado para desarrollo local.
- `docker-compose.prod.yml`: pensado para VPS/produccion.

El compose local monta el repo completo dentro del contenedor y usa `php artisan serve`. Eso es practico para programar, porque cualquier cambio en tu maquina aparece adentro del contenedor.

En produccion eso no conviene. La imagen debe ser una foto cerrada del codigo que se despliega. Por eso `Dockerfile.prod` copia el codigo dentro de la imagen y no depende de tener el repo montado como volumen.

Ventaja:

- El contenedor corre exactamente el codigo con el que fue construido.
- No se mezclan archivos locales, caches o cambios accidentales.
- Es mas facil reproducir un despliegue.

## Por que PHP-FPM y no `php artisan serve`

En desarrollo usamos:

```bash
php artisan serve
```

Ese servidor es comodo, pero no es la opcion correcta para produccion. En produccion usamos:

- Caddy como servidor web publico.
- PHP-FPM como proceso que ejecuta Laravel.

Flujo:

```text
Navegador / Telegram
        |
        v
      Caddy
        |
        v
     PHP-FPM
        |
        v
     Laravel
```

Caddy se ocupa de HTTP/HTTPS, archivos publicos, headers y proxy hacia PHP. PHP-FPM se ocupa de ejecutar PHP de forma mas estable y eficiente.

## Por que Caddy

En `docker-compose.prod.yml` agregamos el servicio `web`, basado en Caddy.

Caddy tiene una ventaja enorme para un VPS chico o mediano: emite y renueva certificados HTTPS automaticamente usando Let's Encrypt.

Esto importa porque:

- Los navegadores esperan HTTPS en produccion.
- Telegram exige HTTPS real para webhooks publicos.
- Las cookies seguras solo viajan por HTTPS.
- Evitamos manejar certificados manualmente al principio.

El dominio se define con:

```env
APP_DOMAIN=potable.tu-dominio.com
APP_URL=https://potable.tu-dominio.com
```

`APP_DOMAIN` lo usa Caddy para saber que dominio servir. `APP_URL` lo usa Laravel para generar URLs correctas.

## Exponer solo Caddy a internet

En produccion, el unico servicio que publica puertos es `web`:

```yaml
ports:
  - "${HTTP_PORT:-80}:80"
  - "${HTTPS_PORT:-443}:443"
```

MySQL no publica puerto al host. Esto es deliberado.

En desarrollo teniamos MySQL expuesto en `3307` para poder conectarnos desde la maquina. En produccion no queremos que la base quede escuchando internet.

Los contenedores se comunican por la red interna Docker:

```text
web -> app -> mysql
```

Desde afuera solo se puede entrar por HTTP/HTTPS.

## Variables de entorno reales

En produccion usamos `.env.production`, creado desde:

```bash
cp .env.production.example .env.production
```

Ese archivo no se versiona. Tiene secretos reales:

- `APP_KEY`
- passwords de MySQL
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_WEBHOOK_SECRET`

El archivo versionado es solo el ejemplo:

```text
.env.production.example
```

Esto permite documentar que variables existen sin subir secretos al repo.

## Como se inyectan las variables en produccion

Hay una diferencia importante entre "hardcodear" y "configurar".

Hardcodear seria escribir secretos directamente en archivos versionados, por ejemplo:

```php
'password' => 'mi-password-real'
```

O subir al repo un archivo con valores reales:

```text
.env.production
```

Eso esta mal porque cualquier persona con acceso al repo o a la imagen podria ver secretos.

Lo que si hacemos es tener un archivo `.env.production` real dentro del VPS, fuera de Git. Ese archivo no se sube al repo, pero existe en el servidor y Docker Compose lo lee al levantar los contenedores.

El flujo queda asi:

```text
repo
  .env.production.example   -> ejemplo versionado, sin secretos reales

VPS
  .env.production           -> archivo real, no versionado, con secretos reales

docker compose
  --env-file .env.production
  env_file:
    - .env.production

contenedores
  reciben APP_KEY, DB_PASSWORD, TELEGRAM_BOT_TOKEN, etc.

Laravel
  lee esas variables con env() durante el boot/config cache
```

En nuestro `docker-compose.prod.yml` usamos:

```yaml
env_file:
  - ${POTABLE_ENV_FILE:-.env.production}
```

Eso significa que por defecto Compose busca `.env.production`. Si algun dia queremos usar otro archivo, podemos pasar:

```bash
POTABLE_ENV_FILE=.env.staging docker compose --env-file .env.staging -f docker-compose.prod.yml up -d
```

La forma esperada para el VPS es:

```bash
cp .env.production.example .env.production
nano .env.production
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

Ese `.env.production` debe vivir en el VPS, con permisos cuidados, y nunca debe commitearse.

Mas adelante, si usamos CI/CD, las variables podrian venir de:

- GitHub Actions Secrets;
- variables protegidas del proveedor de deploy;
- Docker secrets;
- un gestor de secretos como AWS Secrets Manager, Doppler, Vault o 1Password Secrets Automation.

Para esta etapa, `.env.production` en el VPS es una opcion simple y correcta, siempre que no se suba al repo y el servidor este bien protegido.

## `APP_ENV=production`

Esta variable le dice a Laravel en que entorno esta corriendo.

En produccion debe ser:

```env
APP_ENV=production
```

Algunas partes del sistema cambian su comportamiento segun esto. Por ejemplo:

- No se permiten seeders demo.
- `TELEGRAM_WEBHOOK_SECRET` pasa a ser obligatorio.
- Laravel aplica configuraciones propias de entorno no local.

Si se deja `APP_ENV=local` en un VPS, se mezclan comportamientos de desarrollo con produccion.

## `APP_DEBUG=false`

En local queremos ver errores completos. En produccion no.

Debe quedar:

```env
APP_DEBUG=false
```

Si `APP_DEBUG=true`, Laravel puede mostrar stack traces, rutas internas, nombres de tablas, queries y detalles sensibles. Eso ayuda a programar, pero tambien ayuda a un atacante.

La regla simple:

```text
local:      APP_DEBUG=true
produccion: APP_DEBUG=false
```

## `APP_KEY`

`APP_KEY` es la clave criptografica principal de Laravel. Se usa para cifrar y firmar datos internos.

Debe ser unica para cada instalacion productiva.

Se puede generar con:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm app php artisan key:generate --show
```

Luego se copia el valor a:

```env
APP_KEY=base64:...
```

No hay que cambiarla livianamente una vez que el sistema ya esta en uso, porque puede invalidar datos cifrados o sesiones.

## Sesiones y cookies

En produccion dejamos:

```env
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Cada variable cubre un aspecto distinto.

`SESSION_DRIVER=database` guarda las sesiones en MySQL. Es mejor que `file` cuando la app corre en contenedores porque no dependemos de archivos temporales dentro de un contenedor puntual.

`SESSION_ENCRYPT=true` cifra el contenido de la sesion antes de guardarlo.

`SESSION_SECURE_COOKIE=true` hace que la cookie solo viaje por HTTPS.

`SESSION_HTTP_ONLY=true` evita que JavaScript del navegador lea la cookie de sesion.

`SESSION_SAME_SITE=lax` reduce riesgo de CSRF sin romper navegacion normal.

## Base de datos

La base central se configura con:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=potable_central
DB_USERNAME=potable
DB_PASSWORD=...
```

`DB_HOST=mysql` funciona porque dentro de Docker Compose cada servicio puede resolver a los demas por nombre. El contenedor Laravel no se conecta a `127.0.0.1`, sino al servicio `mysql`.

El compose de produccion tambien define:

```env
MYSQL_DATABASE=potable_central
MYSQL_USER=potable
MYSQL_PASSWORD=...
MYSQL_ROOT_PASSWORD=...
```

Esas variables son para inicializar el contenedor de MySQL.

Importante:

- `DB_PASSWORD` y `MYSQL_PASSWORD` deben coincidir.
- `MYSQL_ROOT_PASSWORD` debe ser distinto y fuerte.
- MySQL no debe publicar puerto a internet.

## Tenancy y permisos de MySQL

PoTable usa multi-tenancy con bases separadas por tenant. El init SQL da permisos al usuario `potable` sobre:

```text
potable_central
potable_%
```

Esto permite que la app cree y use bases como:

```text
potable_tenant_los_pinos
potable_tenant_pampa_sur
```

Es una decision practica para este modelo SaaS: Laravel necesita administrar bases tenant. En un entorno mas estricto se podria separar un usuario migrador con permisos altos y otro usuario runtime con permisos mas limitados, pero para este primer despliegue queda simple y funcional.

## Worker de cola

En produccion agregamos un servicio separado:

```yaml
queue:
  command: ["php", "artisan", "queue:work", "database", ...]
```

Laravel puede tener trabajos en segundo plano: emails, tareas pesadas, notificaciones, procesos diferidos. Aunque hoy el sistema no dependa fuertemente de colas, dejar el worker preparado evita una sorpresa cuando se agregue una funcionalidad asincronica.

La idea es que el proceso web no haga trabajos lentos que bloqueen la respuesta del usuario.

## Volumen de storage

El codigo de la imagen es descartable: si reconstruimos la imagen, vuelve a copiarse desde el repo.

Pero `storage/` contiene datos runtime:

- logs
- sesiones si se usara file
- cache local
- archivos subidos
- archivos publicos de storage

Por eso en produccion usamos un volumen Docker:

```yaml
potable_storage:/var/www/html/storage
```

Ese volumen sobrevive a recrear contenedores.

## Symlink `public/storage`

Laravel normalmente usa:

```bash
php artisan storage:link
```

En la imagen productiva dejamos creado el enlace:

```text
public/storage -> storage/app/public
```

Esto permite servir archivos publicos subidos por la app. Hoy no es el centro del sistema, pero es una base correcta para produccion.

## `.dockerignore`

El archivo `.dockerignore` evita copiar cosas peligrosas o innecesarias a la imagen:

- `.env`
- `.env.*`
- `vendor`
- `node_modules`
- caches locales
- logs
- `public/storage`

Esto importa mucho.

Si Docker copiara `.env`, podriamos meter secretos locales dentro de la imagen. Si copiara caches locales, podriamos desplegar rutas o configuracion cacheadas con valores viejos.

La imagen debe construirse limpia y leer la configuracion real desde `.env.production`.

## Composer sin dependencias de desarrollo

En `Dockerfile.prod` se ejecuta:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

Esto evita instalar herramientas de desarrollo en produccion, por ejemplo PHPUnit o Pint.

Ventajas:

- Imagen mas chica.
- Menos superficie de ataque.
- Autoload optimizado.

## Cache de Laravel

En produccion conviene cachear:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Eso reduce trabajo en cada request.

En nuestro entrypoint productivo se ejecuta:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`optimize:clear` limpia caches viejos antes de regenerarlos. Esto ayuda cuando cambiaron variables de entorno o rutas entre despliegues.

Costo: el arranque del contenedor tarda un poco mas.

Beneficio: la app corre con configuracion consistente.

## Trusted hosts

En `bootstrap/app.php` se activo:

```php
$middleware->trustHosts();
```

Esto hace que Laravel confie solo en el host de `APP_URL` y sus subdominios. Ayuda contra ataques basados en headers `Host` manipulados.

Ejemplo:

```env
APP_URL=https://potable.midominio.com
```

Laravel esperara requests para ese dominio.

## Trusted proxies

Tambien agregamos:

```env
TRUSTED_PROXIES=*
```

Y en `bootstrap/app.php` configuramos que Laravel lea headers como:

- `X-Forwarded-For`
- `X-Forwarded-Host`
- `X-Forwarded-Port`
- `X-Forwarded-Proto`
- `X-Forwarded-Prefix`

Esto es importante cuando Laravel esta detras de un proxy, como Caddy, Cloudflare, Nginx Proxy Manager o un balanceador.

Sin esto puede pasar que:

- Laravel crea que la request es HTTP aunque afuera sea HTTPS.
- Genere URLs con `http://`.
- No marque correctamente cookies seguras.
- Calcule mal IPs de clientes.

En un VPS simple con Caddy en el mismo compose, `TRUSTED_PROXIES=*` es una decision practica. En infra mas estricta se podria limitar a IPs concretas del proxy.

## Telegram webhook

Telegram necesita una URL publica HTTPS para enviar mensajes al bot:

```text
https://potable.midominio.com/webhooks/telegram
```

Esa ruta esta definida en `routes/web.php` y apunta a `TelegramWebhookController`.

Para registrar esa URL usamos:

```bash
php artisan potable:telegram-webhook https://potable.midominio.com/webhooks/telegram --drop-pending
```

Ese comando llama a la API de Telegram `setWebhook` y le manda:

- `url`: nuestra ruta publica.
- `secret_token`: el valor de `TELEGRAM_WEBHOOK_SECRET`.
- `allowed_updates`: por ahora solo mensajes.

Luego, cuando Telegram hace un POST a Potable, incluye:

```http
X-Telegram-Bot-Api-Secret-Token: valor-del-secret
```

Potable compara ese header contra `TELEGRAM_WEBHOOK_SECRET`.

Eso evita que cualquiera pueda pegarle libremente al endpoint publico y simular mensajes.

## CSRF y webhook

La ruta del webhook esta excluida de CSRF:

```php
$middleware->validateCsrfTokens(except: [
    'webhooks/telegram',
]);
```

Esto es necesario porque Telegram no tiene una sesion web con nuestro sistema y no puede mandar un token CSRF de Laravel.

Como contrapeso de seguridad usamos:

- `TELEGRAM_WEBHOOK_SECRET`.
- Validacion del formato del mensaje.
- Rate limit por usuario externo de Telegram.

## Rate limits

Agregamos limites en tres puntos:

- Login.
- Generacion de codigos de vinculacion del bot.
- Webhook de Telegram.

El objetivo no es bloquear usuarios normales, sino cortar abuso basico:

- Fuerza bruta contra login.
- Generacion masiva de codigos.
- Spam al webhook.

Los rate limits son una defensa simple y barata. No reemplazan logs ni monitoreo, pero suben bastante el piso de seguridad.

## Datos demo

En produccion no queremos seeders demo ni credenciales prellenadas.

Por eso:

- Los seeders demo se omiten si `APP_ENV=production`.
- El login solo prellena demo en entorno local.

Esto evita dejar cuentas faciles o datos falsos en una instalacion real.

## Logs

En `.env.production.example` usamos:

```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning
```

Esto baja el ruido respecto de desarrollo.

Para una primera version en VPS, logs a archivo/STDOUT alcanzan. Mas adelante se puede mejorar con:

- rotacion de logs;
- envio a un servicio externo;
- alertas ante errores 500;
- monitoreo de uso de disco.

## Migrations

En produccion se ejecutan migraciones con:

```bash
php artisan migrate --force
```

El `--force` es necesario porque Laravel protege comandos destructivos o delicados cuando `APP_ENV=production`.

Para tenants:

```bash
php artisan tenants:migrate --force
```

Regla importante: antes de correr migraciones en produccion conviene tener backup de base de datos.

## Backups

El compose deja datos persistentes en volumenes:

- `potable_mysql_data`
- `potable_storage`
- `caddy_data`

Eso no es backup. Un volumen persistente solo evita perder datos al recrear contenedores.

Un backup real deberia copiar datos fuera del VPS o al menos fuera del volumen Docker:

- dump de MySQL;
- copia de `storage`;
- copia segura de `.env.production`;
- prueba periodica de restauracion.

Este punto todavia queda como tarea operativa pendiente antes de usar el sistema con datos importantes.

## Actualizaciones

Un despliegue normal deberia seguir este orden:

1. Subir el codigo nuevo al VPS.
2. Construir imagenes nuevas.
3. Levantar contenedores.
4. Ejecutar migraciones.
5. Registrar webhook si cambio URL o secret.
6. Revisar logs.

Ejemplo:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan tenants:migrate --force
```

## Que no resolvimos todavia

Esta configuracion deja una base productiva razonable, pero no es el final del camino.

Quedan decisiones futuras:

- backups automaticos;
- monitoreo y alertas;
- rotacion de logs;
- estrategia de restauracion;
- usuario MySQL con permisos mas finos;
- pipeline CI/CD;
- dominios por tenant, si el SaaS lo necesitara;
- envio real de emails;
- almacenamiento externo para archivos si crece el uso.

Para empezar a desplegar en un VPS y probar con usuarios reales controlados, esta base ya es bastante mas correcta que usar el setup local.

## Checklist mental para cualquier proyecto Laravel

Antes de deployar una app Laravel, revisar:

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_KEY` generado y guardado.
- HTTPS funcionando.
- Cookies seguras.
- Base de datos no expuesta publicamente.
- Secrets fuera del repo.
- Dependencias sin vulnerabilidades conocidas.
- Migraciones listas.
- Seeders demo desactivados.
- Logs revisables.
- Backups definidos.
- Webhooks protegidos con secret.
- Rate limits en puntos sensibles.
- Comandos documentados.

La idea central es esta: produccion no es "que ande en otra maquina". Produccion es que ande de forma repetible, segura, observable y recuperable.
