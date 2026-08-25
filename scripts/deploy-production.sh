#!/usr/bin/env bash
set -Eeuo pipefail

BRANCH="${DEPLOY_BRANCH:-master}"
ENV_FILE="${POTABLE_ENV_FILE:-.env.production}"
COMPOSE_FILE="${POTABLE_COMPOSE_FILE:-docker-compose.prod.yml}"
REGISTER_TELEGRAM_WEBHOOK="${REGISTER_TELEGRAM_WEBHOOK:-false}"

COMPOSE=(docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE")

if [ ! -f "$ENV_FILE" ]; then
    echo "No existe $ENV_FILE. Crealo en el VPS a partir de .env.production.example antes de desplegar."
    exit 1
fi

if [ ! -f "$COMPOSE_FILE" ]; then
    echo "No existe $COMPOSE_FILE. Revisa que estes parado en la raiz del proyecto."
    exit 1
fi

echo "Actualizando codigo desde origin/$BRANCH..."
git fetch --prune origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "Levantando MySQL si todavia no esta corriendo..."
"${COMPOSE[@]}" up -d mysql

echo "Construyendo imagenes de produccion..."
"${COMPOSE[@]}" build app queue web

echo "Ejecutando migraciones centrales..."
"${COMPOSE[@]}" run --rm app php artisan migrate --force

echo "Ejecutando migraciones tenant..."
"${COMPOSE[@]}" run --rm app php artisan tenants:migrate --force

echo "Levantando servicios de produccion..."
"${COMPOSE[@]}" up -d --remove-orphans

if [ "$REGISTER_TELEGRAM_WEBHOOK" = "true" ]; then
    echo "Registrando webhook de Telegram..."
    "${COMPOSE[@]}" exec -T app php artisan potable:telegram-webhook
fi

echo "Estado final:"
"${COMPOSE[@]}" ps
