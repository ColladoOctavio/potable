<?php

namespace App\Livewire\Reportes;

use App\Models\Tenant\Gasto;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Venta;
use Livewire\Component;

class ReporteLotes extends Component
{
    public array $filtros = ['lote_id' => '', 'desde' => '', 'hasta' => ''];

    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $lotes = Lote::with('empresa')
            ->where('empresa_id', $empresaId)
            ->when($this->filtros['lote_id'], fn ($q, $v) => $q->whereKey($v))
            ->orderBy('nombre')
            ->get()
            ->map(function (Lote $lote) {
                $ventas = Venta::where('lote_id', $lote->id)
                    ->when($this->filtros['desde'], fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
                    ->when($this->filtros['hasta'], fn ($q, $v) => $q->whereDate('fecha', '<=', $v));
                $gastos = Gasto::where('lote_id', $lote->id)
                    ->when($this->filtros['desde'], fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
                    ->when($this->filtros['hasta'], fn ($q, $v) => $q->whereDate('fecha', '<=', $v));

                $totalVentas = (float) (clone $ventas)->sum('importe_total');
                $totalGastos = (float) (clone $gastos)->sum('importe_total');
                $kilos = (float) (clone $ventas)->sum('kilos');

                $lote->total_ventas = $totalVentas;
                $lote->total_gastos = $totalGastos;
                $lote->resultado = $totalVentas - $totalGastos;
                $lote->costo_hectarea = $lote->hectareas > 0 ? $totalGastos / $lote->hectareas : 0;
                $lote->kilos_hectarea = $lote->hectareas > 0 ? $kilos / $lote->hectareas : 0;

                return $lote;
            });

        return view('livewire.reportes.reporte-lotes', [
            'lotesFiltro' => Lote::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'lotes' => $lotes,
        ]);
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
