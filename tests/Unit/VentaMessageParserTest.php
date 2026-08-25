<?php

namespace Tests\Unit;

use App\Services\Tenant\Ventas\VentaMessageParser;
use PHPUnit\Framework\TestCase;

class VentaMessageParserTest extends TestCase
{
    public function test_parses_sales_message_with_short_date(): void
    {
        $parsed = (new VentaMessageParser)->parse(
            <<<'TXT'
            1/5
            1700 bolsas $8500 Marcos
            60 bolsas $8500 Juan Perez
            TXT,
            2026
        );

        $this->assertTrue($parsed->isValid());
        $this->assertSame('2026-05-01', $parsed->fecha?->toDateString());
        $this->assertCount(2, $parsed->lineas);
        $this->assertSame(1700.0, $parsed->lineas[0]->bolsas);
        $this->assertSame(8500.0, $parsed->lineas[0]->precioPorBolsa);
        $this->assertSame('Marcos', $parsed->lineas[0]->clienteNombre);
        $this->assertSame('Juan Perez', $parsed->lineas[1]->clienteNombre);
    }

    public function test_parses_full_date_and_formatted_prices(): void
    {
        $parsed = (new VentaMessageParser)->parse(
            <<<'TXT'
            01/05/2026
            1 b $8.500 Marcos
            10 bolsas 5.000,50 Juan Perez
            TXT
        );

        $this->assertTrue($parsed->isValid());
        $this->assertSame('2026-05-01', $parsed->fecha?->toDateString());
        $this->assertSame(8500.0, $parsed->lineas[0]->precioPorBolsa);
        $this->assertSame(5000.50, $parsed->lineas[1]->precioPorBolsa);
    }

    public function test_reports_invalid_date_and_sale_line(): void
    {
        $parsed = (new VentaMessageParser)->parse(
            <<<'TXT'
            32/5
            esto no es una venta
            TXT,
            2026
        );

        $this->assertFalse($parsed->isValid());
        $this->assertNull($parsed->fecha);
        $this->assertSame([], $parsed->lineas);
        $this->assertCount(2, $parsed->errores);
    }

    public function test_requires_at_least_one_sale_line(): void
    {
        $parsed = (new VentaMessageParser)->parse('1/5', 2026);

        $this->assertFalse($parsed->isValid());
        $this->assertSame('2026-05-01', $parsed->fecha?->toDateString());
        $this->assertSame(['Agrega al menos una venta debajo de la fecha.'], $parsed->errores);
    }
}
