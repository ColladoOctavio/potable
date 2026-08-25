<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\User;
use App\Services\Central\Bots\BotLinkCodeService;
use App\Services\Central\Bots\BotUserLinkStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BotLinkAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_cannot_create_link_code(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $tenant = $this->createTenant();

        $this->expectException(InvalidArgumentException::class);

        app(BotLinkCodeService::class)->createCode('telegram', $admin->id, $tenant->id, 1);
    }

    public function test_platform_admin_cannot_link_external_user(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $tenant = $this->createTenant();

        $this->expectException(InvalidArgumentException::class);

        app(BotUserLinkStore::class)->link('telegram', '123', $admin->id, $tenant->id, 1);
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'id' => 'cliente1',
            'nombre' => 'Cliente 1',
            'slug' => 'cliente1',
            'database_name' => 'potable_tenant_cliente1',
            'tenancy_db_name' => 'potable_tenant_cliente1',
            'estado' => 'activo',
        ]);
    }
}
