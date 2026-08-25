<?php

namespace App\Services\Tenant\Ventas;

class ParsedVentaLine
{
    public function __construct(
        public readonly int $lineNumber,
        public readonly string $raw,
        public readonly float $bolsas,
        public readonly float $precioPorBolsa,
        public readonly string $clienteNombre,
    ) {}
}
