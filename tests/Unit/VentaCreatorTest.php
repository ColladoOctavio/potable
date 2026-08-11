<?php

namespace Tests\Unit;

use App\Services\Tenant\Ventas\VentaCreator;
use PHPUnit\Framework\TestCase;

class VentaCreatorTest extends TestCase
{
    public function test_normalizes_sale_data_before_persisting(): void
    {
        $data = (new class extends VentaCreator
        {
            public function normalizeForTest(array $data): array
            {
                return $this->normalize($data);
            }

            protected function pesoBolsaEmpresa(int $empresaId): float
            {
                return 25;
            }
        })->normalizeForTest([
            'empresa_id' => 1,
            'cliente_id' => '',
            'lote_id' => '',
            'fecha' => '2026-08-10',
            'descripcion' => 'Venta por bot',
            'bolsas' => 11,
            'precio_por_bolsa' => 5454.55,
            'peso_bolsa_kg' => null,
            'observacion' => null,
        ]);

        $this->assertNull($data['cliente_id']);
        $this->assertNull($data['lote_id']);
        $this->assertArrayNotHasKey('estado_cobro', $data);
        $this->assertSame(25.0, $data['peso_bolsa_kg']);
    }
}
