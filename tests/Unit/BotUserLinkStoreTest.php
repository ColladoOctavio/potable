<?php

namespace Tests\Unit;

use App\Models\Central\BotUserLink;
use App\Services\Central\Bots\BotUserLinkStore;
use InvalidArgumentException;
use Tests\TestCase;

class BotUserLinkStoreTest extends TestCase
{
    public function test_links_external_user_to_potable_context(): void
    {
        $store = new FakeBotUserLinkStore(canAccessTenant: true);

        $link = $store->link(
            channel: 'telegram',
            externalUserId: '123',
            userId: 7,
            tenantId: 'cliente1',
            empresaId: 4,
            externalUsername: 'octavio',
            externalDisplayName: 'Octavio',
        );

        $this->assertSame('telegram', $link->channel);
        $this->assertSame('123', $link->external_user_id);
        $this->assertSame(7, $link->user_id);
        $this->assertSame('cliente1', $link->tenant_id);
        $this->assertSame(4, $link->empresa_id);
        $this->assertTrue((bool) $link->active);
        $this->assertSame('octavio', $link->external_username);
        $this->assertSame('Octavio', $link->external_display_name);
    }

    public function test_rejects_link_when_user_cannot_access_tenant(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FakeBotUserLinkStore(canAccessTenant: false))->link(
            channel: 'telegram',
            externalUserId: '123',
            userId: 7,
            tenantId: 'cliente1',
            empresaId: 4,
        );
    }

    public function test_finds_and_unlinks_existing_context(): void
    {
        $store = new FakeBotUserLinkStore(canAccessTenant: true);
        $link = $store->link('telegram', '123', 7, 'cliente1', 4);

        $this->assertSame($link, $store->findLinkedContext('telegram', '123'));

        $store->touchSeen($link);
        $this->assertSame($link, $store->saved);

        $store->unlink($link);
        $this->assertFalse((bool) $link->active);
    }
}

class FakeBotUserLinkStore extends BotUserLinkStore
{
    public ?BotUserLink $link = null;

    public ?BotUserLink $saved = null;

    public function __construct(private readonly bool $canAccessTenant) {}

    protected function userCanAccessTenant(int $userId, string $tenantId): bool
    {
        return $this->canAccessTenant;
    }

    protected function persistLink(array $attributes): BotUserLink
    {
        $this->link = new BotUserLink;
        $this->link->setRawAttributes($attributes, true);

        return $this->link;
    }

    protected function findActiveLink(string $channel, string $externalUserId): ?BotUserLink
    {
        if (! $this->link || ! $this->link->active) {
            return null;
        }

        if ($this->link->channel !== $channel || $this->link->external_user_id !== $externalUserId) {
            return null;
        }

        return $this->link;
    }

    protected function save(BotUserLink $link): void
    {
        $this->saved = $link;
    }
}
