<?php

namespace Tests\Unit;

use App\Services\Tenant\Ventas\VentaMessageParser;
use App\Services\Tenant\Ventas\VentaMessageResolver;
use PHPUnit\Framework\TestCase;

class VentaMessageResolverTest extends TestCase
{
    public function test_resolves_existing_clients_and_reports_missing_clients(): void
    {
        $parsed = (new VentaMessageParser)->parse(
            <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            60 bolsas $8500 Juan Perez
            TXT,
            2026
        );

        $resolution = $this->resolverWithClients([
            ['id' => 10, 'nombre' => 'Marcos'],
        ])->resolve($parsed, 1);

        $this->assertFalse($resolution->isReady());
        $this->assertCount(1, $resolution->readyLines());
        $this->assertCount(1, $resolution->missingLines());
        $this->assertSame(['Juan Perez'], $resolution->missingClientNames());
        $this->assertSame(10, $resolution->readyLines()[0]->clienteId);
        $this->assertSame(1700.0, $resolution->totalBolsas());
        $this->assertSame(14450000.0, $resolution->totalImporte());
    }

    public function test_matches_client_names_ignoring_case_accents_and_extra_spaces(): void
    {
        $parsed = (new VentaMessageParser)->parse(
            <<<'TXT'
            1/5
            10 bolsas $5000 Jose  Alvarez
            TXT,
            2026
        );

        $resolution = $this->resolverWithClients([
            ['id' => 15, 'nombre' => 'José Alvarez'],
        ])->resolve($parsed, 1);

        $this->assertTrue($resolution->isReady());
        $this->assertSame(15, $resolution->readyLines()[0]->clienteId);
        $this->assertSame('José Alvarez', $resolution->readyLines()[0]->clienteNombre);
    }

    private function resolverWithClients(array $clients): VentaMessageResolver
    {
        return new class($clients) extends VentaMessageResolver
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
        };
    }
}
