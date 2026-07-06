<?php

namespace App\Livewire\Reportes;

use App\Models\Tenant\Gasto;
use App\Models\Tenant\MovimientoCuenta;
use App\Models\Tenant\Proveedor;
use Livewire\Component;

class ReporteProveedores extends Component
{
    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $proveedores = Proveedor::with('empresa')->where('empresa_id', $empresaId)->orderBy('nombre')->get()->map(function (Proveedor $proveedor) {
            $gastos = (float) Gasto::where('proveedor_id', $proveedor->id)->sum('importe_total');
            $pagos = (float) MovimientoCuenta::where('origen_type', Gasto::class)
                ->whereIn('origen_id', Gasto::where('proveedor_id', $proveedor->id)->select('id'))
                ->sum('importe');
            $saldo = (float) $proveedor->saldo_inicial + $gastos - $pagos;
            $proveedor->total_gastado = $gastos;
            $proveedor->total_pagado = $pagos;
            $proveedor->saldo_pendiente = max($saldo, 0);
            $proveedor->saldo_favor = abs(min($saldo, 0));
            $proveedor->gastos_abiertos = Gasto::where('proveedor_id', $proveedor->id)->whereIn('estado_pago', ['pendiente', 'parcial'])->count();

            return $proveedor;
        });

        return view('livewire.reportes.reporte-proveedores', compact('proveedores'));
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
