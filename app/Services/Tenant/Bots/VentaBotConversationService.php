<?php

namespace App\Services\Tenant\Bots;

use App\Models\Tenant\BotPendingAction;
use App\Services\Tenant\Ventas\VentaMessageImporter;
use App\Services\Tenant\Ventas\VentaMessageParser;
use App\Services\Tenant\Ventas\VentaMessagePendingPayload;
use App\Services\Tenant\Ventas\VentaMessagePreviewFormatter;
use App\Services\Tenant\Ventas\VentaMessageResolver;
use App\Services\Tenant\Ventas\VentaMissingClientCreator;
use Illuminate\Support\Str;

class VentaBotConversationService
{
    public function __construct(
        private readonly BotPendingActionStore $actions,
        private readonly VentaMessageParser $parser,
        private readonly VentaMessageResolver $resolver,
        private readonly VentaMessagePreviewFormatter $formatter,
        private readonly VentaMessagePendingPayload $payloads,
        private readonly VentaMessageImporter $importer,
        private readonly VentaMissingClientCreator $missingClients,
    ) {}

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
        $this->actions->expireOld();

        $text = trim($text);

        if ($this->isConfirmation($text)) {
            return $this->confirmPending($channel, $externalUserId);
        }

        if ($this->isCancellation($text)) {
            return $this->cancelPending($channel, $externalUserId);
        }

        return $this->startSalesFlow($channel, $externalUserId, $text, $userId, $empresaId, $loteId, $pesoBolsaKg, $empresaNombre);
    }

    private function startSalesFlow(
        string $channel,
        string $externalUserId,
        string $text,
        ?int $userId,
        int $empresaId,
        ?int $loteId,
        ?float $pesoBolsaKg,
        ?string $empresaNombre,
    ): string {
        $parsed = $this->parser->parse($text);
        $resolution = $this->resolver->resolve($parsed, $empresaId);
        $message = $this->formatter->format($resolution, $empresaNombre);

        if (! $resolution->isReady()) {
            if ($resolution->parsed->isValid() && $resolution->missingLines() !== []) {
                $this->cancelCurrentPending($channel, $externalUserId);

                $this->actions->createPending(
                    channel: $channel,
                    externalUserId: $externalUserId,
                    userId: $userId,
                    empresaId: $empresaId,
                    type: BotPendingAction::TYPE_CREAR_CLIENTES_Y_CARGAR_VENTAS,
                    payload: $this->payloads->fromMissingResolution($resolution, $loteId, $pesoBolsaKg),
                );
            }

            return $message;
        }

        $this->cancelCurrentPending($channel, $externalUserId);

        $this->actions->createPending(
            channel: $channel,
            externalUserId: $externalUserId,
            userId: $userId,
            empresaId: $empresaId,
            type: BotPendingAction::TYPE_CARGAR_VENTAS,
            payload: $this->payloads->fromResolution($resolution, $loteId, $pesoBolsaKg),
        );

        return $message;
    }

    private function confirmPending(string $channel, string $externalUserId): string
    {
        $action = $this->actions->findPending($channel, $externalUserId);

        if (! $action) {
            return 'No tengo ventas pendientes para confirmar.';
        }

        if ($action->type === BotPendingAction::TYPE_CREAR_CLIENTES_Y_CARGAR_VENTAS) {
            return $this->confirmCreateClientsAndSales($action);
        }

        $payload = $action->payload ?? [];
        $resolution = $this->payloads->toResolution($payload);
        $ventas = $this->importer->import(
            resolution: $resolution,
            empresaId: (int) $action->empresa_id,
            loteId: $payload['lote_id'] ?? null,
            pesoBolsaKg: $payload['peso_bolsa_kg'] ?? null,
        );

        $this->actions->complete($action);

        return 'Listo, cargue '.count($ventas).' ventas por '.$this->formatMoney($resolution->totalImporte()).'.';
    }

    private function confirmCreateClientsAndSales(BotPendingAction $action): string
    {
        $payload = $action->payload ?? [];
        $parsed = $this->payloads->toParsedMessage($payload);
        $resolution = $this->resolver->resolve($parsed, (int) $action->empresa_id);
        $createdClients = $this->missingClients->createMissing($resolution->missingClientNames(), (int) $action->empresa_id);
        $resolution = $this->resolver->resolve($parsed, (int) $action->empresa_id);
        $ventas = $this->importer->import(
            resolution: $resolution,
            empresaId: (int) $action->empresa_id,
            loteId: $payload['lote_id'] ?? null,
            pesoBolsaKg: $payload['peso_bolsa_kg'] ?? null,
        );

        $this->actions->complete($action);

        return 'Listo, cree '.count($createdClients).' clientes y cargue '.count($ventas).' ventas por '.$this->formatMoney($resolution->totalImporte()).'.';
    }

    private function cancelPending(string $channel, string $externalUserId): string
    {
        $action = $this->actions->findPending($channel, $externalUserId);

        if (! $action) {
            return 'No tengo ventas pendientes para cancelar.';
        }

        $this->actions->cancel($action);

        return 'Carga cancelada. No se guardo ninguna venta.';
    }

    private function cancelCurrentPending(string $channel, string $externalUserId): void
    {
        if ($pending = $this->actions->findPending($channel, $externalUserId)) {
            $this->actions->cancel($pending);
        }
    }

    private function isConfirmation(string $text): bool
    {
        return in_array($this->normalizeCommand($text), ['si', 's', 'ok', 'confirmar', 'confirmo'], true);
    }

    private function isCancellation(string $text): bool
    {
        return in_array($this->normalizeCommand($text), ['no', 'n', 'cancelar', 'cancelo'], true);
    }

    private function normalizeCommand(string $text): string
    {
        $text = mb_strtolower(Str::ascii(trim($text)));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function formatMoney(float $value): string
    {
        return '$ '.number_format($value, 2, ',', '.');
    }
}
