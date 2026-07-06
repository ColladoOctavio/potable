<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantProvisioner
{
    public function makeDatabaseName(string $slug): string
    {
        return 'potable_tenant_'.Str::slug($slug, '_');
    }

    public function createTenant(string $nombre, string $slug, string $estado = 'prueba', ?int $ownerUserId = null): Tenant
    {
        $slug = Str::slug($slug);
        $database = $this->makeDatabaseName($slug);

        return Tenant::updateOrCreate(
            ['id' => $slug],
            [
                'nombre' => $nombre,
                'slug' => $slug,
                'database_name' => $database,
                'tenancy_db_name' => $database,
                'estado' => $estado,
                'owner_user_id' => $ownerUserId,
            ],
        );
    }

    public function ensureDatabase(Tenant $tenant): void
    {
        $database = str_replace('`', '', $tenant->database_name);
        $charset = config('database.connections.mysql.charset', 'utf8mb4');
        $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET `{$charset}` COLLATE `{$collation}`");
    }
}
