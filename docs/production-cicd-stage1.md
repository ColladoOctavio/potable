# CI/CD etapa 1

Esta etapa automatiza el despliegue sin cambiar todavia a un registry de imagenes Docker.

La idea es:

```text
push a master
    |
    v
GitHub Actions CI
    |
    |-- instala dependencias
    |-- corre formato/tests/audits
    |
    v
Railway puede esperar esos checks y desplegar si pasan
```

Para VPS, ademas queda un workflow manual:

```text
GitHub Actions VPS production deploy
    |
    v
entra por SSH al VPS
    |
    v
scripts/deploy-production.sh
    |
    |-- git pull
    |-- docker compose build
    |-- migraciones
    |-- docker compose up -d
```

## Que archivos participan

- `.github/workflows/ci.yml`: workflow automatico de tests para GitHub/Railway.
- `.github/workflows/production-deploy.yml`: workflow manual para deploy por VPS.
- `scripts/deploy-production.sh`: script que se ejecuta dentro del VPS.
- `.env.production`: archivo real del VPS, no versionado.
- `docker-compose.prod.yml`: define los servicios de produccion.

## Quien ejecuta cada cosa

`ci.yml` y `production-deploy.yml` no son archivos de Docker. Son recetas de GitHub Actions.

Cuando esos archivos estan subidos al repo, GitHub los detecta automaticamente porque estan dentro de:

```text
.github/workflows/
```

Entonces, cuando haces push a `master`, pasa esto:

```text
1. GitHub ve que cambio el branch master.
2. GitHub busca workflows en .github/workflows/.
3. GitHub encuentra ci.yml.
4. GitHub crea una maquina temporal propia, llamada runner.
5. En ese runner corre los pasos del job test.
6. Railway puede esperar ese resultado si activamos Wait for CI.
```

O sea:

```text
ci.yml
  lo ejecuta GitHub Actions automaticamente en una maquina de GitHub.

production-deploy.yml
  queda disponible para dispararlo manualmente si usamos VPS.

scripts/deploy-production.sh
  lo ejecuta el VPS, pero solo cuando se dispara el workflow manual de VPS.
```

Vos podrias correr `scripts/deploy-production.sh` manualmente por SSH, y de hecho conviene hacerlo una vez para probar si vamos por VPS. En Railway no hace falta.

## Que conexiones SSH hay

Hay dos conexiones distintas que suelen confundirse.

Primera conexion:

```text
GitHub Actions -> VPS
```

Esta es la conexion que permite que GitHub entre al servidor y ejecute el deploy.

Para eso usamos:

- clave privada en GitHub Secret `PRODUCTION_SSH_KEY`;
- clave publica en `~/.ssh/authorized_keys` del usuario del VPS.

Segunda conexion:

```text
VPS -> GitHub
```

Esta ocurre cuando el script del VPS ejecuta:

```bash
git pull --ff-only origin master
```

Si el repo es publico, normalmente no hace falta ninguna key especial para hacer pull por HTTPS.

Si el repo es privado, el VPS tambien necesita permiso para leer el repo. Hay varias formas:

- usar una deploy key SSH de GitHub;
- usar un token personal de GitHub;
- clonar el repo usando SSH con una clave autorizada.

En ese caso, la clave/token para que el VPS lea GitHub vive en el VPS, no en el repo.

## Responsabilidad de GitHub Actions

GitHub Actions no guarda el `.env.production` de Laravel.

Su trabajo es:

- bajar el codigo del repo;
- instalar dependencias;
- correr `pint`;
- correr tests;
- correr audits;
- avisarle a GitHub/Railway si el codigo esta sano.

Si usamos VPS, el workflow manual necesita secretos para conectarse:

- `PRODUCTION_HOST`
- `PRODUCTION_USER`
- `PRODUCTION_SSH_KEY`
- `PRODUCTION_PATH`
- `PRODUCTION_SSH_PORT` opcional

## Responsabilidad del VPS

El VPS guarda lo que no debe vivir en Git:

- `.env.production`;
- volumenes Docker;
- base de datos;
- certificados de Caddy;
- datos persistentes de `storage`.

El VPS tambien tiene el repo clonado. Cuando GitHub entra por SSH, ejecuta:

```bash
cd /opt/potable
bash scripts/deploy-production.sh
```

## Preparar el VPS

Instalar como minimo:

- Git.
- Docker.
- Docker Compose plugin.

Clonar el repo:

```bash
sudo mkdir -p /opt/potable
sudo chown "$USER":"$USER" /opt/potable
git clone https://github.com/ColladoOctavio/potable.git /opt/potable
cd /opt/potable
```

Crear el entorno real:

```bash
cp .env.production.example .env.production
nano .env.production
```

Completar `APP_URL`, `APP_DOMAIN`, `APP_KEY`, passwords de MySQL y tokens de Telegram.

Probar manualmente antes de conectar GitHub Actions:

```bash
bash scripts/deploy-production.sh
```

## Preparar la clave SSH

Desde tu maquina local, generar una clave dedicada para GitHub Actions:

```bash
ssh-keygen -t ed25519 -C "github-actions-potable-production" -f ~/.ssh/potable_github_actions
```

Esto crea:

```text
~/.ssh/potable_github_actions      -> clave privada
~/.ssh/potable_github_actions.pub  -> clave publica
```

La clave publica va al VPS:

```bash
ssh-copy-id -i ~/.ssh/potable_github_actions.pub usuario@tu-vps
```

La clave privada va a GitHub Secrets como `PRODUCTION_SSH_KEY`.

## Configurar GitHub Secrets

En GitHub:

```text
Repo -> Settings -> Secrets and variables -> Actions -> New repository secret
```

Crear:

```text
PRODUCTION_HOST=ip-o-dominio-del-vps
PRODUCTION_USER=usuario-ssh
PRODUCTION_PATH=/opt/potable
PRODUCTION_SSH_KEY=contenido completo de ~/.ssh/potable_github_actions
```

Opcional:

```text
PRODUCTION_SSH_PORT=22
```

Si no se define, el workflow usa `22`.

## Como se despliega

Cuando pusheas a `master`, GitHub ejecuta el CI:

```yaml
on:
  push:
    branches:
      - master
```

Primero corre el job `test`.

Si falla un test, no despliega.

Si usamos Railway, Railway puede deployar automaticamente desde GitHub. Si activamos `Wait for CI`, Railway espera que este workflow termine bien antes de desplegar.

Si usamos VPS, se dispara manualmente el workflow `VPS production deploy`, que entra por SSH y ejecuta:

```bash
bash scripts/deploy-production.sh
```

## Que hace el script de deploy

El script del VPS hace:

```bash
git fetch --prune origin master
git checkout master
git pull --ff-only origin master
docker compose --env-file .env.production -f docker-compose.prod.yml up -d mysql
docker compose --env-file .env.production -f docker-compose.prod.yml build app queue web
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm app php artisan tenants:migrate --force
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --remove-orphans
```

`git pull --ff-only` evita merges raros en el VPS. Si el VPS tiene cambios locales, el deploy falla en vez de pisarlos silenciosamente.

## Por que reconstruimos imagenes

En produccion el codigo no se monta como volumen dentro de `app`.

El codigo se copia dentro de la imagen Docker. Por eso, despues del `git pull`, hay que reconstruir:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml build app queue web
```

Si solo hicieramos `git pull`, los contenedores seguirian corriendo con la imagen anterior.

## Migraciones

El script corre migraciones antes de reemplazar los servicios:

```bash
php artisan migrate --force
php artisan tenants:migrate --force
```

Esto aplica cambios de base central y bases tenant.

Regla practica: las migraciones productivas deberian ser compatibles con el codigo viejo durante unos segundos, porque puede haber un periodo corto entre migrar y reiniciar servicios.

## Webhook de Telegram

El script no registra el webhook de Telegram en cada deploy.

Eso es intencional: si la URL y el secret no cambiaron, no hace falta.

Si queres forzarlo en un deploy manual:

```bash
REGISTER_TELEGRAM_WEBHOOK=true bash scripts/deploy-production.sh
```

Normalmente solo se registra cuando:

- cambia `APP_URL`;
- cambia `TELEGRAM_WEBHOOK_SECRET`;
- se configura el VPS por primera vez;
- se cambia de dominio.

## Ambientes protegidos

El workflow usa:

```yaml
environment: production
```

En GitHub se puede configurar ese environment para requerir aprobacion manual antes de desplegar.

Ruta:

```text
Repo -> Settings -> Environments -> production
```

Se puede agregar:

- required reviewers;
- secrets propios de production;
- reglas de ramas.

Esto es muy util si no queremos que cada push a `master` despliegue sin confirmacion.

## Costo

Para esta etapa, el costo suele ser cero o muy bajo.

GitHub Actions tiene minutos gratis incluidos segun el tipo de cuenta/repositorio. En repos publicos, los runners estandar suelen ser gratis. En repos privados hay una cuota mensual incluida y despues se cobra excedente.

Esta etapa no usa registry de imagenes, asi que no agrega costo de almacenamiento de imagenes.

El costo real principal sigue siendo el VPS.

## Limitaciones de esta etapa

Esta etapa es simple y buena para arrancar, pero tiene limites:

- El build se hace en el VPS.
- El VPS necesita tener Git y acceso al repo.
- El rollback es manual.
- No queda publicada una imagen inmutable por version.
- Si el repo es privado, hay que configurar acceso Git en el VPS.

La etapa 2 resolveria eso construyendo imagenes en GitHub Actions y publicandolas en un registry como GHCR.

## Rollback manual

Si un deploy salio mal, se puede volver a un commit anterior:

```bash
ssh usuario@tu-vps
cd /opt/potable
git checkout COMMIT_ANTERIOR
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

Si hubo migraciones destructivas, el rollback de codigo puede no alcanzar. Por eso antes de migraciones importantes conviene tener backup.

## Checklist antes de activar

- El VPS tiene Docker funcionando.
- El repo esta clonado en `PRODUCTION_PATH`.
- `.env.production` existe en el VPS.
- El deploy manual con `bash scripts/deploy-production.sh` funciona.
- La clave publica SSH esta autorizada en el VPS.
- La clave privada SSH esta en GitHub Secrets.
- Los tests pasan localmente.
- El branch productivo es `master`.
- GitHub environment `production` esta configurado si se quiere aprobacion manual.
