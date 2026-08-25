<?php

namespace Tests\Unit;

use App\Models\Tenant\BotPendingAction;
use App\Services\Tenant\Bots\BotPendingActionStore;
use App\Services\Tenant\Bots\VentaBotConversationService;
use App\Services\Tenant\Ventas\VentaCreator;
use App\Services\Tenant\Ventas\VentaMessageImporter;
use App\Services\Tenant\Ventas\VentaMessageParser;
use App\Services\Tenant\Ventas\VentaMessagePendingPayload;
use App\Services\Tenant\Ventas\VentaMessagePreviewFormatter;
use App\Services\Tenant\Ventas\VentaMessageResolution;
use App\Services\Tenant\Ventas\VentaMessageResolver;
use App\Services\Tenant\Ventas\VentaMissingClientCreator;
use Carbon\CarbonInterface;
use Tests\TestCase;

class VentaBotConversationServiceTest extends TestCase
{
    public function test_starts_sales_flow_and_stores_pending_confirmation(): void
    {
        [$service, $store] = $this->service([
            ['id' => 10, 'nombre' => 'Marcos'],
            ['id' => 11, 'nombre' => 'Juan Perez'],
        ]);

        $response = $service->handleMessage(
            channel: 'telegram',
            externalUserId: '123',
            text: <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            60 bolsas $8500 Juan Perez
            TXT,
            userId: 7,
            empresaId: 4,
            empresaNombre: 'Campo La Papa SA',
        );

        $this->assertStringContainsString('Empresa: Campo La Papa SA', $response);
        $this->assertStringContainsString('Ventas listas para cargar (01/05/2026):', $response);
        $this->assertStringContainsString('Respondé SI para cargar o NO para cancelar.', $response);
        $this->assertCount(1, $store->created);
        $this->assertSame('telegram', $store->created[0]['channel']);
        $this->assertSame('123', $store->created[0]['externalUserId']);
        $this->assertSame(7, $store->created[0]['userId']);
        $this->assertSame(4, $store->created[0]['empresaId']);
        $this->assertSame(BotPendingAction::TYPE_CARGAR_VENTAS, $store->created[0]['type']);
        $this->assertSame('2026-05-01', $store->created[0]['payload']['fecha']);
    }

    public function test_confirms_pending_sales_and_marks_action_completed(): void
    {
        [$service, $store, $importer] = $this->service([
            ['id' => 10, 'nombre' => 'Marcos'],
        ]);
        $service->handleMessage(
            channel: 'telegram',
            externalUserId: '123',
            text: <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            TXT,
            userId: 7,
            empresaId: 4,
        );

        $response = $service->handleMessage('telegram', '123', 'sí', 7, 4);

        $this->assertSame('Listo, cargue 1 ventas por $ 14.450.000,00.', $response);
        $this->assertSame($store->pending, $store->completed);
        $this->assertSame(4, $importer->empresaId);
        $this->assertSame(14450000.0, $importer->resolution?->totalImporte());
    }

    public function test_cancels_pending_sales(): void
    {
        [$service, $store] = $this->service([
            ['id' => 10, 'nombre' => 'Marcos'],
        ]);
        $service->handleMessage(
            channel: 'telegram',
            externalUserId: '123',
            text: <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            TXT,
            userId: 7,
            empresaId: 4,
        );

        $response = $service->handleMessage('telegram', '123', 'NO', 7, 4);

        $this->assertSame('Carga cancelada. No se guardo ninguna venta.', $response);
        $this->assertSame($store->pending, $store->cancelled);
    }

    public function test_stores_pending_client_creation_when_clients_are_missing(): void
    {
        [$service, $store] = $this->service([]);

        $response = $service->handleMessage(
            channel: 'telegram',
            externalUserId: '123',
            text: <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            TXT,
            userId: 7,
            empresaId: 4,
        );

        $this->assertStringContainsString('faltan clientes cargados', $response);
        $this->assertStringContainsString('Responde SI para crear esos clientes', $response);
        $this->assertCount(1, $store->created);
        $this->assertSame(BotPendingAction::TYPE_CREAR_CLIENTES_Y_CARGAR_VENTAS, $store->created[0]['type']);
        $this->assertSame(['Marcos'], $store->created[0]['payload']['missing_client_names']);
    }

    public function test_confirms_missing_clients_creates_clients_and_sales(): void
    {
        [$service, $store, $importer, $clientCreator] = $this->service([]);
        $service->handleMessage(
            channel: 'telegram',
            externalUserId: '123',
            text: <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            TXT,
            userId: 7,
            empresaId: 4,
        );

        $response = $service->handleMessage('telegram', '123', 'SI', 7, 4);

        $this->assertSame('Listo, cree 1 clientes y cargue 1 ventas por $ 14.450.000,00.', $response);
        $this->assertSame(['Marcos'], $clientCreator->createdNames);
        $this->assertSame($store->pending, $store->completed);
        $this->assertSame(4, $importer->empresaId);
        $this->assertSame(14450000.0, $importer->resolution?->totalImporte());
    }

    public function test_confirms_without_pending_action(): void
    {
        [$service] = $this->service([]);

        $this->assertSame(
            'No tengo ventas pendientes para confirmar.',
            $service->handleMessage('telegram', '123', 'SI', 7, 4),
        );
    }

    private function service(array $clients): array
    {
        $store = new FakeBotPendingActionStore;
        $importer = new FakeVentaMessageImporter;
        $resolver = new FakeVentaMessageResolverForConversation($clients);
        $clientCreator = new FakeVentaMissingClientCreator($resolver);

        return [
            new VentaBotConversationService(
                actions: $store,
                parser: new VentaMessageParser,
                resolver: $resolver,
                formatter: new VentaMessagePreviewFormatter,
                payloads: new VentaMessagePendingPayload,
                importer: $importer,
                missingClients: $clientCreator,
            ),
            $store,
            $importer,
            $clientCreator,
        ];
    }
}

class FakeVentaMessageResolverForConversation extends VentaMessageResolver
{
    public function __construct(private array $clients) {}

    public function addClient(int $id, string $name): void
    {
        $this->clients[] = ['id' => $id, 'nombre' => $name];
    }

    protected function clientesIndex(int $empresaId): array
    {
        $index = [];

        foreach ($this->clients as $client) {
            $index[$this->normalizeName($client['nombre'])] = $client;
        }

        return $index;
    }
}

class FakeVentaMissingClientCreator extends VentaMissingClientCreator
{
    public array $createdNames = [];

    private int $nextId = 100;

    public function __construct(private readonly FakeVentaMessageResolverForConversation $resolver) {}

    public function createMissing(array $clientNames, int $empresaId): array
    {
        foreach ($clientNames as $clientName) {
            $this->createdNames[] = $clientName;
            $this->resolver->addClient($this->nextId++, $clientName);
        }

        return array_map(fn (string $name) => ['nombre' => $name], $clientNames);
    }
}

class FakeBotPendingActionStore extends BotPendingActionStore
{
    public array $created = [];

    public ?BotPendingAction $pending = null;

    public ?BotPendingAction $completed = null;

    public ?BotPendingAction $cancelled = null;

    public int $expireOldCalls = 0;

    public function createPending(
        string $channel,
        string $externalUserId,
        ?int $userId,
        int $empresaId,
        string $type,
        array $payload,
        ?CarbonInterface $expiresAt = null,
    ): BotPendingAction {
        $this->created[] = compact('channel', 'externalUserId', 'userId', 'empresaId', 'type', 'payload', 'expiresAt');
        $this->pending = $this->makeAction($channel, $externalUserId, $userId, $empresaId, $type, $payload);

        return $this->pending;
    }

    public function findPending(string $channel, string $externalUserId, ?string $type = null): ?BotPendingAction
    {
        if (! $this->pending || $this->pending->status !== BotPendingAction::STATUS_PENDING) {
            return null;
        }

        if ($this->pending->channel !== $channel || $this->pending->external_user_id !== $externalUserId) {
            return null;
        }

        if ($type && $this->pending->type !== $type) {
            return null;
        }

        return $this->pending;
    }

    public function complete(BotPendingAction $action): void
    {
        $action->status = BotPendingAction::STATUS_COMPLETED;
        $this->completed = $action;
    }

    public function cancel(BotPendingAction $action): void
    {
        $action->status = BotPendingAction::STATUS_CANCELLED;
        $this->cancelled = $action;
    }

    public function expireOld(): int
    {
        $this->expireOldCalls++;

        return 0;
    }

    private function makeAction(string $channel, string $externalUserId, ?int $userId, int $empresaId, string $type, array $payload): BotPendingAction
    {
        $action = new BotPendingAction;
        $action->setRawAttributes([
            'id' => 1,
            'channel' => $channel,
            'external_user_id' => $externalUserId,
            'user_id' => $userId,
            'empresa_id' => $empresaId,
            'type' => $type,
            'payload' => json_encode($payload),
            'status' => BotPendingAction::STATUS_PENDING,
        ], true);

        return $action;
    }
}

class FakeVentaMessageImporter extends VentaMessageImporter
{
    public ?VentaMessageResolution $resolution = null;

    public ?int $empresaId = null;

    public function __construct()
    {
        parent::__construct(new VentaCreator);
    }

    public function import(VentaMessageResolution $resolution, int $empresaId, ?int $loteId = null, ?float $pesoBolsaKg = null): array
    {
        $this->resolution = $resolution;
        $this->empresaId = $empresaId;

        return array_fill(0, count($resolution->readyLines()), ['ok' => true]);
    }
}
