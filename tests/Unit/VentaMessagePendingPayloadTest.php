<?php

namespace Tests\Unit;

use App\Services\Tenant\Ventas\VentaMessageParser;
use App\Services\Tenant\Ventas\VentaMessagePendingPayload;
use App\Services\Tenant\Ventas\VentaMessageResolver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VentaMessagePendingPayloadTest extends TestCase
{
    public function test_builds_json_safe_payload_and_reconstructs_resolution(): void
    {
        $resolution = $this->resolve(
            <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            60 bolsas $8500 Juan Perez
            TXT,
            [
                ['id' => 10, 'nombre' => 'Marcos'],
                ['id' => 11, 'nombre' => 'Juan Perez'],
            ]
        );

        $payload = (new VentaMessagePendingPayload)->fromResolution($resolution, loteId: 8, pesoBolsaKg: 20);

        $this->assertSame('2026-05-01', $payload['fecha']);
        $this->assertSame(8, $payload['lote_id']);
        $this->assertSame(20.0, $payload['peso_bolsa_kg']);
        $this->assertSame(10, $payload['lineas'][0]['cliente_id']);
        $this->assertSame('1700 bolsas $8500 Marcos', $payload['lineas'][0]['raw']);

        $reconstructed = (new VentaMessagePendingPayload)->toResolution($payload);

        $this->assertTrue($reconstructed->isReady());
        $this->assertSame('2026-05-01', $reconstructed->parsed->fecha?->toDateString());
        $this->assertSame(1760.0, $reconstructed->totalBolsas());
        $this->assertSame(14960000.0, $reconstructed->totalImporte());
    }

    public function test_rejects_resolution_with_missing_clients(): void
    {
        $resolution = $this->resolve(
            <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            TXT,
            []
        );

        $this->expectException(InvalidArgumentException::class);

        (new VentaMessagePendingPayload)->fromResolution($resolution);
    }

    private function resolve(string $message, array $clients)
    {
        $parsed = (new VentaMessageParser)->parse($message, 2026);

        return (new class($clients) extends VentaMessageResolver
        {
            public function __construct(private readonly array $clients) {}

            protected function clientesIndex(int $empresaId): array
            {
                $index = [];

                foreach ($this->clients as $client) {
                    $index[$this->normalizeName($client['nombre'])] = $client;
                }

                return $index;
            }
        })->resolve($parsed, 1);
    }
}
