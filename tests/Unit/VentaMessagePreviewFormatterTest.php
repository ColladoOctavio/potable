<?php

namespace Tests\Unit;

use App\Services\Tenant\Ventas\VentaMessageParser;
use App\Services\Tenant\Ventas\VentaMessagePreviewFormatter;
use App\Services\Tenant\Ventas\VentaMessageResolver;
use PHPUnit\Framework\TestCase;

class VentaMessagePreviewFormatterTest extends TestCase
{
    public function test_formats_ready_resolution_for_confirmation(): void
    {
        $resolution = $this->resolve(
            <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            60 bolsas $8500 Juan Perez
            TXT,
            [
                ['id' => 1, 'nombre' => 'Marcos'],
                ['id' => 2, 'nombre' => 'Juan Perez'],
            ]
        );

        $message = (new VentaMessagePreviewFormatter)->format($resolution, 'Campo La Papa SA');

        $this->assertStringContainsString('Empresa: Campo La Papa SA', $message);
        $this->assertStringContainsString('Ventas listas para cargar (01/05/2026):', $message);
        $this->assertStringContainsString('Marcos: 1.700 bolsas x $ 8.500,00 = $ 14.450.000,00', $message);
        $this->assertStringContainsString('Juan Perez: 60 bolsas x $ 8.500,00 = $ 510.000,00', $message);
        $this->assertStringContainsString('Total bolsas: 1.760', $message);
        $this->assertStringContainsString('Total: $ 14.960.000,00', $message);
        $this->assertStringContainsString('Respondé SI para cargar o NO para cancelar.', $message);
    }

    public function test_formats_missing_clients_message(): void
    {
        $resolution = $this->resolve(
            <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            60 bolsas $8500 Juan Perez
            TXT,
            [
                ['id' => 1, 'nombre' => 'Marcos'],
            ]
        );

        $message = (new VentaMessagePreviewFormatter)->format($resolution);

        $this->assertStringContainsString('faltan clientes cargados', $message);
        $this->assertStringContainsString('- Juan Perez', $message);
        $this->assertStringContainsString('Ventas que sí reconocí:', $message);
        $this->assertStringContainsString('No cargue ninguna venta. Responde SI para crear esos clientes y cargar las ventas, o NO para cancelar.', $message);
    }

    public function test_formats_parser_errors(): void
    {
        $resolution = $this->resolve(
            <<<'TXT'
            32/5
            esto no es una venta
            TXT,
            []
        );

        $message = (new VentaMessagePreviewFormatter)->format($resolution);

        $this->assertStringContainsString('No pude leer el mensaje completo:', $message);
        $this->assertStringContainsString('Linea 1: la fecha no es valida.', $message);
        $this->assertStringContainsString('Linea 2: no pude leer la venta.', $message);
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
