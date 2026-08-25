<?php

namespace Tests\Unit;

use App\Models\Central\BotLinkCode;
use App\Models\Central\BotUserLink;
use App\Services\Central\Bots\BotLinkCodeService;
use App\Services\Central\Bots\BotUserLinkStore;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BotLinkCodeServiceTest extends TestCase
{
    public function test_creates_formatted_link_code(): void
    {
        $service = new FakeBotLinkCodeService(new FakeBotUserLinkStoreForCodes, canAccessTenant: true);

        $issue = $service->createCode('telegram', 7, 'cliente1', 4, CarbonImmutable::parse('2026-08-10 12:00:00'));

        $this->assertSame('ABCD-2345', $issue->code);
        $this->assertSame('telegram', $service->persisted?->channel);
        $this->assertSame(7, $service->persisted?->user_id);
        $this->assertSame('cliente1', $service->persisted?->tenant_id);
        $this->assertSame(4, $service->persisted?->empresa_id);
        $this->assertNotSame('ABCD-2345', $service->persisted?->code_hash);
        $this->assertTrue($service->expiredPreviousCodes);
    }

    public function test_rejects_code_creation_without_tenant_access(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FakeBotLinkCodeService(new FakeBotUserLinkStoreForCodes, canAccessTenant: false))
            ->createCode('telegram', 7, 'cliente1', 4);
    }

    public function test_consumes_code_and_creates_user_link(): void
    {
        $links = new FakeBotUserLinkStoreForCodes;
        $service = new FakeBotLinkCodeService($links, canAccessTenant: true);
        $service->validCode = $service->makeCodeModel([
            'channel' => 'telegram',
            'user_id' => 7,
            'tenant_id' => 'cliente1',
            'empresa_id' => 4,
        ]);

        $link = $service->consumeCode('telegram', 'abcd-2345', '123', 'octavio', 'Octavio');

        $this->assertSame($link, $links->link);
        $this->assertSame('telegram', $links->linkedData['channel']);
        $this->assertSame('123', $links->linkedData['externalUserId']);
        $this->assertSame(7, $links->linkedData['userId']);
        $this->assertSame('cliente1', $links->linkedData['tenantId']);
        $this->assertSame(4, $links->linkedData['empresaId']);
        $this->assertSame($service->validCode, $service->consumedCode);
    }
}

class FakeBotLinkCodeService extends BotLinkCodeService
{
    public ?BotLinkCode $persisted = null;

    public ?BotLinkCode $validCode = null;

    public ?BotLinkCode $consumedCode = null;

    public bool $expiredPreviousCodes = false;

    public function __construct(BotUserLinkStore $links, private readonly bool $canAccessTenant)
    {
        parent::__construct($links);
    }

    protected function persistCode(array $attributes): BotLinkCode
    {
        return $this->persisted = $this->makeCodeModel($attributes);
    }

    protected function findValidCode(string $channel, string $code): ?BotLinkCode
    {
        return $this->validCode;
    }

    protected function markConsumed(BotLinkCode $linkCode): void
    {
        $this->consumedCode = $linkCode;
    }

    protected function expirePreviousCodes(string $channel, int $userId, string $tenantId, int $empresaId): void
    {
        $this->expiredPreviousCodes = true;
    }

    protected function userCanAccessTenant(int $userId, string $tenantId): bool
    {
        return $this->canAccessTenant;
    }

    protected function newCode(): string
    {
        return 'ABCD2345';
    }

    public function makeCodeModel(array $attributes): BotLinkCode
    {
        $code = new BotLinkCode;
        $code->setRawAttributes($attributes, true);

        return $code;
    }
}

class FakeBotUserLinkStoreForCodes extends BotUserLinkStore
{
    public array $linkedData = [];

    public ?BotUserLink $link = null;

    public function link(
        string $channel,
        string $externalUserId,
        int $userId,
        string $tenantId,
        int $empresaId,
        ?string $externalUsername = null,
        ?string $externalDisplayName = null,
    ): BotUserLink {
        $this->linkedData = compact('channel', 'externalUserId', 'userId', 'tenantId', 'empresaId', 'externalUsername', 'externalDisplayName');

        $this->link = new BotUserLink;
        $this->link->setRawAttributes([
            'channel' => $channel,
            'external_user_id' => $externalUserId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'empresa_id' => $empresaId,
            'external_username' => $externalUsername,
            'external_display_name' => $externalDisplayName,
            'active' => true,
        ], true);

        return $this->link;
    }
}
