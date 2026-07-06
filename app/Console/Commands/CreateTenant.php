<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Tenant\TenantProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CreateTenant extends Command
{
    protected $signature = 'potable:tenant
        {nombre : Nombre visible del tenant}
        {slug : Slug usado para la base potable_tenant_{slug}}
        {--owner= : Email del usuario administrador}
        {--estado=prueba : activo, suspendido o prueba}
        {--seed : Cargar datos demo en el tenant}';

    protected $description = 'Crea un tenant central, su base MySQL y ejecuta migraciones tenant.';

    public function handle(TenantProvisioner $provisioner): int
    {
        $owner = $this->option('owner') ? User::where('email', $this->option('owner'))->first() : null;
        $tenant = $provisioner->createTenant($this->argument('nombre'), $this->argument('slug'), $this->option('estado'), $owner?->id);

        if ($owner) {
            $tenant->users()->syncWithoutDetaching([$owner->id => ['rol' => 'administrador']]);
        }

        $provisioner->ensureDatabase($tenant);

        Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);
        $this->line(Artisan::output());

        if ($this->option('seed')) {
            Artisan::call('tenants:seed', ['--tenants' => [$tenant->id]]);
            $this->line(Artisan::output());
        }

        $this->info("Tenant {$tenant->nombre} listo en {$tenant->database_name}.");

        return self::SUCCESS;
    }
}
