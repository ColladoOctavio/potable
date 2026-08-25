<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Tenant\TenantProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Seeder demo omitido en producción.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@potable.test'],
            ['name' => 'Admin PoTable', 'password' => 'password', 'is_platform_admin' => true],
        );

        $demo = User::updateOrCreate(
            ['email' => 'demo@potable.test'],
            ['name' => 'Demo Papero', 'password' => 'password', 'is_platform_admin' => false],
        );

        $provisioner = app(TenantProvisioner::class);

        $tenants = collect([
            $provisioner->createTenant('Los Pinos Papas', 'los-pinos', 'activo', $admin->id),
            $provisioner->createTenant('Pampa Sur Productores', 'pampa-sur', 'prueba', $admin->id),
        ]);

        foreach ($tenants as $tenant) {
            $provisioner->ensureDatabase($tenant);
        }

        $admin->tenants()->syncWithoutDetaching($tenants->mapWithKeys(fn ($tenant) => [
            $tenant->id => ['rol' => 'administrador'],
        ])->all());

        $demo->tenants()->sync([
            $tenants->first()->id => ['rol' => 'administrador'],
        ]);

        Artisan::call('tenants:migrate');
        Artisan::call('tenants:seed');
    }
}
