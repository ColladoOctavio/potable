# PoTable

PoTable es un proyecto personal full stack en desarrollo orientado a la gestión comercial y contable de productores y pequeñas empresas. La aplicación centraliza ventas, gastos, clientes, proveedores, lotes, cuentas y reportes, e incorpora una integración con Telegram para registrar ventas mediante una interfaz conversacional.

## Presentación del proyecto

El proyecto fue desarrollado con foco en una arquitectura SaaS multi-tenant. Cada tenant dispone de su propia base de datos y puede administrar múltiples empresas, manteniendo separada la información de cada cliente.

Entre las funcionalidades principales se incluyen:

- Gestión de empresas, lotes, clientes, proveedores y categorías de gasto.
- Registro y seguimiento de ventas, gastos y movimientos de cuenta.
- Cuenta única por empresa con cálculo de saldo.
- Dashboard y reportes de ventas, gastos, clientes, proveedores y lotes.
- Vista individual por empresa y vista consolidada.
- Integración con un bot de Telegram para registrar ventas desde el chat.
- Administración central de tenants y usuarios de la plataforma.

### Stack principal

- PHP y Laravel
- Blade y Livewire
- Bootstrap 5
- MySQL
- Docker y Docker Compose
- Eloquent ORM, migraciones y seeders
- Multi-database tenancy con `stancl/tenancy`
- GitHub Actions para CI

### Aspectos técnicos destacados

- Separación de datos mediante multi-database tenancy.
- Aprovisionamiento de nuevos tenants, incluyendo creación de base, migraciones y datos demo.
- Integración con la API de Telegram mediante webhooks, vinculación de usuarios y flujo conversacional para carga de ventas.
- Tests unitarios y de integración para autenticación, Telegram y lógica de negocio.
- Configuración de CI con validación de formato, ejecución de tests y auditoría de dependencias.
- Configuración de despliegue con Docker, PHP-FPM, MySQL y Caddy con HTTPS automático.

> **Estado:** proyecto personal en desarrollo. El repositorio incluye datos y credenciales exclusivamente de demostración para facilitar la ejecución local.

---

## Documentación técnica y puesta en marcha

PoTable es una primera maqueta funcional de SaaS contable simple para productores o empresas paperas.

Stack principal:

- Laravel, Blade y Livewire
- Bootstrap 5
- MySQL con Docker
- Eloquent, migraciones y seeders
- Multi-database tenancy con `stancl/tenancy`

## Puesta en marcha

```bash
docker compose up -d
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --force
php artisan serve
```

La app queda disponible en:

```text
http://127.0.0.1:8000
```

El layout carga Bootstrap por CDN y CSS propio desde `public/css/potable.css`, por lo que puede navegarse sin compilar assets. Vite queda configurado para desarrollo de assets; requiere Node 20.19+ por las versiones actuales de Vite/Laravel Vite.

```bash
npm install
npm run dev
```

## MySQL local

`docker-compose.yml` levanta solo MySQL:

- Contenedor: `potable_db`
- Host: `127.0.0.1`
- Puerto local: `3307`
- Base central: `potable_central`
- Usuario: `potable`
- Password: `secret`

El init SQL otorga permisos para crear bases `potable_tenant_*`.

## Usuario demo

```text
admin@potable.test / password  # administrador de plataforma, ve todos los tenants
demo@potable.test / password   # usuario cliente, entra a un unico tenant
```

## Tenants demo

El seeder central crea:

- `los-pinos` / base `potable_tenant_los_pinos`
- `pampa-sur` / base `potable_tenant_pampa_sur`

Cada tenant se carga con empresas, lotes, clientes, proveedores, categorias, ventas, gastos, cuenta unica y movimientos.

## Modelo de acceso

- Un usuario cliente pertenece a un unico tenant.
- Un tenant puede tener muchos usuarios.
- Dentro de un tenant se pueden administrar muchas empresas.
- Solo los usuarios con `is_platform_admin = true` pueden seleccionar entre todos los tenants del SaaS.

## Crear un tenant nuevo

```bash
php artisan potable:tenant "Nombre del Tenant" nombre-del-tenant --owner=admin@potable.test --estado=prueba --seed
```

El comando crea el registro central, la base `potable_tenant_nombre_del_tenant`, ejecuta migraciones tenant y opcionalmente carga datos demo con `--seed`.

## Modulos incluidos

- Login central y logout
- Seleccion de tenant
- Dashboard con selector de empresa o vista consolidada
- CRUDs de empresas, lotes, clientes, proveedores y categorias de gasto
- Formularios Livewire de ventas, gastos y movimientos de cuenta
- Cuenta unica por empresa con saldo calculado
- Reportes general, ventas, gastos, clientes, proveedores y lotes

## Verificacion

```bash
php artisan test
php artisan view:cache
```

En el entorno usado para esta maqueta, `npm run build` queda bloqueado por Node `18.19.1`; Vite 8 requiere Node `20.19+`.

## Produccion

La configuracion base para VPS esta en:

- `.env.production.example`
- `docker-compose.prod.yml`
- `Dockerfile.prod`
- `Dockerfile.local`
- `docs/production-deploy.md`
- `docs/production-configuration-decisions.md`
- `docs/production-cicd-stage1.md`

El despliegue productivo usa Caddy con HTTPS automatico, PHP-FPM, MySQL y un worker de colas.
Railway usa autodeteccion desde GitHub; por eso el Dockerfile local no esta en la raiz como `Dockerfile`.
