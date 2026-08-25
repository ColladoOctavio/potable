<?php

namespace App\Services\Tenant\Ventas;

class VentaMessageResolution
{
    /**
     * @param  array<int, ResolvedVentaLine>  $lineas
     */
    public function __construct(
        public readonly ParsedVentaMessage $parsed,
        public readonly array $lineas,
    ) {}

    public function isReady(): bool
    {
        return $this->parsed->isValid() && $this->missingLines() === [];
    }

    /**
     * @return array<int, ResolvedVentaLine>
     */
    public function readyLines(): array
    {
        return array_values(array_filter($this->lineas, fn (ResolvedVentaLine $linea) => $linea->isResolved()));
    }

    /**
     * @return array<int, ResolvedVentaLine>
     */
    public function missingLines(): array
    {
        return array_values(array_filter($this->lineas, fn (ResolvedVentaLine $linea) => ! $linea->isResolved()));
    }

    /**
     * @return array<int, string>
     */
    public function missingClientNames(): array
    {
        return array_values(array_unique(array_map(
            fn (ResolvedVentaLine $linea) => $linea->parsed->clienteNombre,
            $this->missingLines(),
        )));
    }

    public function totalBolsas(): float
    {
        return array_sum(array_map(fn (ResolvedVentaLine $linea) => $linea->parsed->bolsas, $this->readyLines()));
    }

    public function totalImporte(): float
    {
        return array_sum(array_map(fn (ResolvedVentaLine $linea) => $linea->importeTotal(), $this->readyLines()));
    }
}
