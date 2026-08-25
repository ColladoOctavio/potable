<?php

namespace Tests\Feature;

use App\Models\Central\BotUserLink;
use App\Models\Central\Tenant;
use App\Models\User;
use App\Services\Central\Bots\BotUserLinkStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotUserLinkAccessValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_link_when_user_still_has_tenant_access(): void
    {
        [$user, $tenant] = $this->createLinkedUser();
        $link = $this->createLink($user, $tenant);

        $found = app(BotUserLinkStore::class)->findLinkedContext('telegram', '123');

        $this->assertTrue($found?->is($link));
        $this->assertTrue($link->fresh()->active);
    }

    public function test_deactivates_link_when_user_lost_tenant_access(): void
    {
        [$user, $tenant] = $this->createLinkedUser();
        $link = $this->createLink($user, $tenant);
        $user->tenants()->detach($tenant->id);

        $found = app(BotUserLinkStore::class)->findLinkedContext('telegram', '123');

        $this->assertNull($found);
        $this->assertFalse($link->fresh()->active);
    }

    public function test_deactivates_link_when_linked_user_is_platform_admin(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $tenant = $this->createTenant();
        $admin->tenants()->attach($tenant->id, ['rol' => 'administrador']);
        $link = $this->createLink($admin, $tenant);

        $found = app(BotUserLinkStore::class)->findLinkedContext('telegram', '123');

        $this->assertNull($found);
        $this->assertFalse($link->fresh()->active);
    }

    /**
     * @return array{0:User, 1:Tenant}
     */
    private function createLinkedUser(): array
    {
        $user = User::factory()->create(['is_platform_admin' => false]);
        $tenant = $this->createTenant();
        $user->tenants()->attach($tenant->id, ['rol' => 'administrador']);

        return [$user, $tenant];
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

    private function createLink(User $user, Tenant $tenant): BotUserLink
    {
        return BotUserLink::create([
            'channel' => 'telegram',
            'external_user_id' => '123',
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'empresa_id' => 1,
            'active' => true,
            'linked_at' => now(),
        ]);
    }
}
