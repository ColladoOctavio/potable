<?php

namespace App\Services\Tenant\Ventas;

use Carbon\CarbonImmutable;

class ParsedVentaMessage
{
    /**
     * @param  array<int, ParsedVentaLine>  $lineas
     * @param  array<int, string>  $errores
     */
    public function __construct(
        public readonly ?CarbonImmutable $fecha,
        public readonly array $lineas,
        public readonly array $errores,
    ) {}

    public function isValid(): bool
    {
        return $this->fecha !== null && $this->lineas !== [] && $this->errores === [];
    }
}
