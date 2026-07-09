<?php

namespace App\Services\Central;

use App\Models\Central\Tenant;
use App\Models\Tenant\Empresa;
use App\Models\User;
use App\Services\Tenant\TenantProvisioner;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class SaasClientCreator
{
    public function __construct(private readonly TenantProvisioner $tenantProvisioner)
    {
    }

    /**
     * @param  array{
     *     tenant_nombre:string,
     *     tenant_slug:string,
     *     estado:string,
     *     owner_name:string,
     *     owner_email:string,
     *     owner_password:string,
     *     empresa_nombre:string,
     *     empresa_cuit?:?string,
     *     empresa_email?:?string,
     *     empresa_telefono?:?string
     * }  $data
     */
    public function create(array $data): Tenant
    {
        $tenant = $this->tenantProvisioner->createTenant(
            $data['tenant_nombre'],
            $data['tenant_slug'],
            $data['estado'],
        );

        $this->tenantProvisioner->ensureDatabase($tenant);
        $this->migrateTenant($tenant);

        $owner = User::create([
            'name' => $data['owner_name'],
            'email' => $data['owner_email'],
            'password' => $data['owner_password'],
            'is_platform_admin' => false,
        ]);

        $tenant->forceFill(['owner_user_id' => $owner->id])->save();
        $tenant->users()->syncWithoutDetaching([
            $owner->id => ['rol' => 'administrador'],
        ]);

        $tenant->run(function () use ($data): void {
            Empresa::firstOrCreate(
                ['nombre' => $data['empresa_nombre']],
                [
                    'cuit' => $data['empresa_cuit'] ?? null,
                    'email' => $data['empresa_email'] ?? null,
                    'telefono' => $data['empresa_telefono'] ?? null,
                    'observacion' => 'Empresa inicial creada al dar de alta el cliente.',
                ],
            );
        });

        return $tenant->refresh()->load('owner');
    }

    private function migrateTenant(Tenant $tenant): void
    {
        $exitCode = Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->getTenantKey()],
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(Artisan::output() ?: 'No se pudieron correr las migraciones del tenant.');
        }
    }
}
