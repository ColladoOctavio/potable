# Despliegue de produccion

Esta configuracion esta pensada para un VPS con Docker Compose.

Para entender por que se eligio esta configuracion, leer tambien `docs/production-configuration-decisions.md`.

Para automatizar despliegues desde GitHub Actions, leer `docs/production-cicd-stage1.md`.

## Que levanta

- `web`: Caddy, sirve HTTPS y los archivos publicos de Laravel.
- `app`: PHP-FPM con Laravel.
- `queue`: worker de Laravel para colas en base de datos.
- `mysql`: MySQL 8.4 sin puerto publico expuesto.

## Preparacion del VPS

El dominio debe apuntar a la IP publica del VPS. Los puertos 80 y 443 tienen que estar abiertos para que Caddy pueda emitir el certificado HTTPS.

Crear el archivo de entorno de produccion a partir de `.env.production.example`:

```bash
cp .env.production.example .env.production
```

Ese `.env.production` se crea en el VPS y no se sube al repo. Es la forma en que Docker Compose inyecta secretos reales a los contenedores.

Completar como minimo:

- `APP_URL`, por ejemplo `https://potable.midominio.com`.
- `APP_DOMAIN`, por ejemplo `potable.midominio.com`.
- `APP_KEY`.
- `DB_PASSWORD` y `MYSQL_PASSWORD` con el mismo valor.
- `MYSQL_ROOT_PASSWORD`.
- `TELEGRAM_BOT_TOKEN`.
- `TELEGRAM_WEBHOOK_SECRET`.

Para generar `APP_KEY`:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm app php artisan key:generate --show
```

## Levantar produccion

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

Ejecutar migraciones centrales:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan migrate --force
```

Si ya existen tenants, ejecutar tambien sus migraciones:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan tenants:migrate --force
```

Registrar el webhook de Telegram:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan potable:telegram-webhook https://potable.midominio.com/webhooks/telegram --drop-pending
```

## Comandos utiles

Ver logs:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f web app queue
```

Reiniciar solo Laravel:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml restart app queue
```

Verificar estado:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml ps
```

## Notas de seguridad

- `APP_DEBUG` debe quedar en `false`.
- `APP_ENV` debe quedar en `production`.
- `SESSION_SECURE_COOKIE` debe quedar en `true`.
- `TELEGRAM_WEBHOOK_SECRET` debe ser largo, random y distinto del token del bot.
- MySQL no publica puerto hacia internet en `docker-compose.prod.yml`.
- No ejecutar seeders demo en produccion.
