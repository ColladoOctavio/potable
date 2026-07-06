<?php

namespace App\Livewire\Reportes;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\MovimientoCuenta;
use App\Models\Tenant\Venta;
use Livewire\Component;

class ReporteClientes extends Component
{
    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $clientes = Cliente::with('empresa')->where('empresa_id', $empresaId)->orderBy('nombre')->get()->map(function (Cliente $cliente) {
            $ventas = (float) Venta::where('cliente_id', $cliente->id)->sum('importe_total');
            $cobros = (float) MovimientoCuenta::where('origen_type', Venta::class)
                ->whereIn('origen_id', Venta::where('cliente_id', $cliente->id)->select('id'))
                ->sum('importe');
            $saldo = (float) $cliente->saldo_inicial + $ventas - $cobros;
            $cliente->total_vendido = $ventas;
            $cliente->total_cobrado = $cobros;
            $cliente->saldo_pendiente = max($saldo, 0);
            $cliente->saldo_favor = abs(min($saldo, 0));
            $cliente->ventas_abiertas = Venta::where('cliente_id', $cliente->id)->whereIn('estado_cobro', ['pendiente', 'parcial'])->count();

            return $cliente;
        });

        return view('livewire.reportes.reporte-clientes', compact('clientes'));
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
