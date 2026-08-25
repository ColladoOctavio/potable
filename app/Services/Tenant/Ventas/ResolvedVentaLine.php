<?php

namespace App\Services\Tenant\Ventas;

class ResolvedVentaLine
{
    public function __construct(
        public readonly ParsedVentaLine $parsed,
        public readonly ?int $clienteId,
        public readonly ?string $clienteNombre,
    ) {}

    public function isResolved(): bool
    {
        return $this->clienteId !== null;
    }

    public function importeTotal(): float
    {
        return $this->parsed->bolsas * $this->parsed->precioPorBolsa;
    }
}
