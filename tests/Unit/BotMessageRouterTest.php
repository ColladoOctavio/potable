<?php

namespace Tests\Unit;

use App\Models\Central\BotUserLink;
use App\Services\Central\Bots\BotLinkCodeService;
use App\Services\Central\Bots\BotMessageRouter;
use App\Services\Central\Bots\BotUserLinkStore;
use App\Services\Tenant\Bots\BotEmpresaSelectionService;
use App\Services\Tenant\Bots\BotPendingActionStore;
use App\Services\Tenant\Bots\VentaBotConversationService;
use App\Services\Tenant\Ventas\VentaCreator;
use App\Services\Tenant\Ventas\VentaMessageImporter;
use App\Services\Tenant\Ventas\VentaMessageParser;
use App\Services\Tenant\Ventas\VentaMessagePendingPayload;
use App\Services\Tenant\Ventas\VentaMessagePreviewFormatter;
use App\Services\Tenant\Ventas\VentaMessageResolver;
use App\Services\Tenant\Ventas\VentaMissingClientCreator;
use InvalidArgumentException;
use Tests\TestCase;

class BotMessageRouterTest extends TestCase
{
    public function test_requires_link_for_regular_messages(): void
    {
        [$router] = $this->router();

        $response = $router->handleMessage('telegram', '123', '1/5');

        $this->assertStringContainsString('no esta vinculada', $response);
        $this->assertStringContainsString('/vincular CODIGO', $response);
    }

    public function test_consumes_link_code(): void
    {
        $parts = $this->router();
        $router = $parts[0];
        $links = $parts['links'];
        $codes = $parts['codes'];
        $codes->linkToReturn = $this->link(wasRecentlyCreated: true);

        $response = $router->handleMessage('telegram', '123', '/vincular ABCD-2345', 'octavio', 'Octavio');

        $this->assertStringContainsString('Telegram quedo vinculado', $response);
        $this->assertStringContainsString('Empresa actual: Campo La Papa SA.', $response);
        $this->assertSame('ABCD-2345', $codes->code);
        $this->assertSame('octavio', $codes->externalUsername);
        $this->assertNull($links->touched);
    }

    public function test_reports_when_link_code_updates_existing_telegram_link(): void
    {
        $parts = $this->router();
        $router = $parts[0];
        $codes = $parts['codes'];
        $codes->linkToReturn = $this->link(wasRecentlyCreated: false);

        $response = $router->handleMessage('telegram', '123', '/vincular ABCD-2345');

        $this->assertStringContainsString('actualice la vinculacion', $response);
        $this->assertStringContainsString('Empresa actual: Campo La Papa SA.', $response);
    }

    public function test_reports_when_link_code_moves_telegram_link_to_another_tenant(): void
    {
        $parts = $this->router();
        $router = $parts[0];
        $codes = $parts['codes'];
        $codes->linkToReturn = $this->link(
            tenantId: 'cliente1',
            userId: 7,
            wasRecentlyCreated: false,
            previousTenantId: 'los-pinos',
            previousUserId: 3,
            previousUserEmail: 'demo@potable.test',
            linkedUserEmail: 'cliente1@gmail.com',
        );

        $response = $router->handleMessage('telegram', '123', '/vincular ABCD-2345');

        $this->assertStringContainsString('Estaba vinculada a la cuenta de Potable "demo@potable.test"', $response);
        $this->assertStringContainsString('ahora quedo vinculada a "cliente1@gmail.com"', $response);
        $this->assertStringContainsString('Empresa actual: Campo La Papa SA.', $response);
    }

    public function test_reports_invalid_link_code(): void
    {
        $parts = $this->router();
        $router = $parts[0];
        $codes = $parts['codes'];
        $codes->exception = new InvalidArgumentException('El codigo de vinculacion no existe o vencio.');

        $this->assertSame(
            'El codigo de vinculacion no existe o vencio.',
            $router->handleMessage('telegram', '123', '/vincular ABCD-2345'),
        );
    }

    public function test_routes_company_commands_for_linked_user(): void
    {
        $parts = $this->router(link: $this->link());
        $router = $parts[0];
        $links = $parts['links'];
        $empresas = $parts['empresas'];

        $this->assertSame('lista empresas', $router->handleMessage('telegram', '123', '/empresa'));
        $this->assertSame($links->link, $links->touched);

        $this->assertSame('empresa cambiada', $router->handleMessage('telegram', '123', '/usar 2'));
        $this->assertSame('/usar 2', $empresas->usedText);
    }

    public function test_routes_sales_messages_to_sales_conversation(): void
    {
        $parts = $this->router(link: $this->link());
        $router = $parts[0];
        $ventas = $parts['ventas'];

        $response = $router->handleMessage('telegram', '123', "1/5\n10 bolsas \$5000 Marcos");

        $this->assertSame('preview ventas', $response);
        $this->assertSame('telegram', $ventas->channel);
        $this->assertSame('123', $ventas->externalUserId);
        $this->assertSame(7, $ventas->userId);
        $this->assertSame(4, $ventas->empresaId);
        $this->assertSame('Campo La Papa SA', $ventas->empresaNombre);
    }

    private function router(?BotUserLink $link = null): array
    {
        $links = new FakeBotUserLinkStoreForRouter($link);
        $codes = new FakeBotLinkCodeServiceForRouter($links);
        $empresas = new FakeBotEmpresaSelectionServiceForRouter($links);
        $ventas = new FakeVentaBotConversationServiceForRouter;

        return [
            new class($codes, $links, $empresas, $ventas) extends BotMessageRouter
            {
                protected function withTenant(BotUserLink $link, callable $callback): string
                {
                    return $callback();
                }
            },
            'links' => $links,
            'codes' => $codes,
            'empresas' => $empresas,
            'ventas' => $ventas,
        ];
    }

    private function link(
        string $tenantId = 'cliente1',
        int $userId = 7,
        bool $wasRecentlyCreated = true,
        ?string $previousTenantId = null,
        ?int $previousUserId = null,
        ?string $previousUserEmail = null,
        ?string $linkedUserEmail = null,
    ): BotUserLink {
        $link = new BotUserLink;
        $link->setRawAttributes([
            'channel' => 'telegram',
            'external_user_id' => '123',
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'empresa_id' => 4,
            'active' => true,
        ], true);
        $link->wasRecentlyCreated = $wasRecentlyCreated;
        $link->previousTenantId = $previousTenantId;
        $link->previousUserId = $previousUserId;
        $link->previousUserEmail = $previousUserEmail;
        $link->linkedUserEmail = $linkedUserEmail;

        return $link;
    }
}

class FakeBotUserLinkStoreForRouter extends BotUserLinkStore
{
    public ?BotUserLink $touched = null;

    public function __construct(public ?BotUserLink $link = null) {}

    public function findLinkedContext(string $channel, string $externalUserId): ?BotUserLink
    {
        return $this->link;
    }

    public function touchSeen(BotUserLink $link): void
    {
        $this->touched = $link;
    }
}

class FakeBotLinkCodeServiceForRouter extends BotLinkCodeService
{
    public ?BotUserLink $linkToReturn = null;

    public ?InvalidArgumentException $exception = null;

    public ?string $code = null;

    public ?string $externalUsername = null;

    public function consumeCode(
        string $channel,
        string $code,
        string $externalUserId,
        ?string $externalUsername = null,
        ?string $externalDisplayName = null,
    ): BotUserLink {
        if ($this->exception) {
            throw $this->exception;
        }

        $this->code = $code;
        $this->externalUsername = $externalUsername;

        return $this->linkToReturn;
    }
}

class FakeBotEmpresaSelectionServiceForRouter extends BotEmpresaSelectionService
{
    public ?string $usedText = null;

    public function listEmpresas(BotUserLink $link): string
    {
        return 'lista empresas';
    }

    public function useEmpresa(BotUserLink $link, string $text): string
    {
        $this->usedText = $text;

        return 'empresa cambiada';
    }

    public function currentEmpresaName(int $empresaId): ?string
    {
        return $empresaId === 4 ? 'Campo La Papa SA' : null;
    }
}

class FakeVentaBotConversationServiceForRouter extends VentaBotConversationService
{
    public ?string $channel = null;

    public ?string $externalUserId = null;

    public ?int $userId = null;

    public ?int $empresaId = null;

    public ?string $empresaNombre = null;

    public function __construct()
    {
        parent::__construct(
            new BotPendingActionStore,
            new VentaMessageParser,
            new VentaMessageResolver,
            new VentaMessagePreviewFormatter,
            new VentaMessagePendingPayload,
            new VentaMessageImporter(new VentaCreator),
            new VentaMissingClientCreator,
        );
    }

    public function handleMessage(
        string $channel,
        string $externalUserId,
        string $text,
        ?int $userId,
        int $empresaId,
        ?int $loteId = null,
        ?float $pesoBolsaKg = null,
        ?string $empresaNombre = null,
    ): string {
        $this->channel = $channel;
        $this->externalUserId = $externalUserId;
        $this->userId = $userId;
        $this->empresaId = $empresaId;
        $this->empresaNombre = $empresaNombre;

        return 'preview ventas';
    }
}
