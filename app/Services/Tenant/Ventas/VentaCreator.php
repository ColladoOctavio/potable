<?php

namespace App\Services\Tenant\Ventas;

use App\Models\Tenant\Empresa;
use App\Models\Tenant\Venta;

class VentaCreator
{
    public function create(array $data): Venta
    {
        return Venta::create($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $data['cliente_id'] = $data['cliente_id'] ?: null;
        $data['lote_id'] = $data['lote_id'] ?: null;
        $data['peso_bolsa_kg'] = $data['peso_bolsa_kg'] ?: $this->pesoBolsaEmpresa((int) $data['empresa_id']);

        return $data;
    }

    protected function pesoBolsaEmpresa(int $empresaId): float
    {
        return (float) (Empresa::whereKey($empresaId)->value('peso_bolsa_kg') ?? 20);
    }
}
