<?php

namespace Tests\Unit;

use App\Services\Tenant\Ventas\VentaCreator;
use App\Services\Tenant\Ventas\VentaMessageImporter;
use App\Services\Tenant\Ventas\VentaMessageParser;
use App\Services\Tenant\Ventas\VentaMessageResolver;
use InvalidArgumentException;
use Tests\TestCase;

class VentaMessageImporterTest extends TestCase
{
    public function test_imports_ready_resolution_as_sales(): void
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
        $importer = $this->fakeImporter();

        $ventas = $importer->import($resolution, empresaId: 4, loteId: 8, pesoBolsaKg: 20);

        $this->assertCount(2, $ventas);
        $this->assertTrue($importer->transactionCalled);
        $this->assertSame([
            'empresa_id' => 4,
            'cliente_id' => 10,
            'lote_id' => 8,
            'fecha' => '2026-05-01',
            'descripcion' => 'Venta por mensaje - Marcos',
            'bolsas' => 1700.0,
            'precio_por_bolsa' => 8500.0,
            'peso_bolsa_kg' => 20.0,
            'observacion' => 'Cargada desde mensaje. Linea 2: 1700 bolsas $8500 Marcos',
        ], $importer->createdData[0]);
        $this->assertSame(11, $importer->createdData[1]['cliente_id']);
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
        $importer = $this->fakeImporter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('faltan clientes: Marcos');

        $importer->import($resolution, empresaId: 4);
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

    private function fakeImporter()
    {
        return new class(new VentaCreator) extends VentaMessageImporter
        {
            public array $createdData = [];

            public bool $transactionCalled = false;

            protected function createVenta(array $data)
            {
                $this->createdData[] = $data;

                return $data;
            }

            protected function transaction(callable $callback): mixed
            {
                $this->transactionCalled = true;

                return $callback();
            }
        };
    }
}
