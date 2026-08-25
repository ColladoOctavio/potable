<?php

namespace App\Services\Tenant\Ventas;

use App\Models\Tenant\Venta;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VentaMessageImporter
{
    public function __construct(private readonly VentaCreator $ventaCreator) {}

    /**
     * @return array<int, Venta>
     */
    public function import(VentaMessageResolution $resolution, int $empresaId, ?int $loteId = null, ?float $pesoBolsaKg = null): array
    {
        $this->ensureReady($resolution);

        return $this->transaction(function () use ($resolution, $empresaId, $loteId, $pesoBolsaKg) {
            $ventas = [];

            foreach ($resolution->readyLines() as $line) {
                $ventas[] = $this->createVenta($this->dataForLine($resolution, $line, $empresaId, $loteId, $pesoBolsaKg));
            }

            return $ventas;
        });
    }

    protected function createVenta(array $data)
    {
        return $this->ventaCreator->create($data);
    }

    protected function transaction(callable $callback): mixed
    {
        return DB::connection('tenant')->transaction($callback);
    }

    private function ensureReady(VentaMessageResolution $resolution): void
    {
        if ($resolution->parsed->errores !== []) {
            throw new InvalidArgumentException('No se puede cargar un mensaje con errores de formato.');
        }

        if ($resolution->missingLines() !== []) {
            throw new InvalidArgumentException('No se puede cargar porque faltan clientes: '.implode(', ', $resolution->missingClientNames()).'.');
        }

        if (! $resolution->isReady()) {
            throw new InvalidArgumentException('No hay ventas listas para cargar.');
        }
    }

    private function dataForLine(VentaMessageResolution $resolution, ResolvedVentaLine $line, int $empresaId, ?int $loteId, ?float $pesoBolsaKg): array
    {
        return [
            'empresa_id' => $empresaId,
            'cliente_id' => $line->clienteId,
            'lote_id' => $loteId,
            'fecha' => $resolution->parsed->fecha?->toDateString(),
            'descripcion' => $this->descriptionFor($line),
            'bolsas' => $line->parsed->bolsas,
            'precio_por_bolsa' => $line->parsed->precioPorBolsa,
            'peso_bolsa_kg' => $pesoBolsaKg,
            'observacion' => "Cargada desde mensaje. Linea {$line->parsed->lineNumber}: {$line->parsed->raw}",
        ];
    }

    private function descriptionFor(ResolvedVentaLine $line): string
    {
        return mb_substr('Venta por mensaje - '.$line->parsed->clienteNombre, 0, 255);
    }
}
