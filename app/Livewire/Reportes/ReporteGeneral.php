<?php

namespace App\Livewire\Reportes;

use App\Models\Tenant\Cuenta;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\MovimientoCuenta;
use App\Models\Tenant\Venta;
use Livewire\Component;

class ReporteGeneral extends Component
{
    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $totalVentas = (float) Venta::where('empresa_id', $empresaId)->sum('importe_total');
        $totalGastos = (float) Gasto::where('empresa_id', $empresaId)->sum('importe_total');
        $ingresos = (float) MovimientoCuenta::where('empresa_id', $empresaId)->where('tipo', 'ingreso')->sum('importe');
        $egresos = (float) MovimientoCuenta::where('empresa_id', $empresaId)->where('tipo', 'egreso')->sum('importe');
        $ajustes = (float) MovimientoCuenta::where('empresa_id', $empresaId)->where('tipo', 'ajuste')->sum('importe');
        $saldo = (float) Cuenta::where('empresa_id', $empresaId)->sum('saldo_inicial') + $ingresos - $egresos + $ajustes;

        return view('livewire.reportes.reporte-general', [
            'summary' => [
                'ventas' => $totalVentas,
                'gastos' => $totalGastos,
                'resultado' => $totalVentas - $totalGastos,
                'pendiente_cobro' => max($totalVentas - $ingresos, 0),
                'pendiente_pago' => max($totalGastos - $egresos, 0),
                'saldo' => $saldo,
            ],
            'empresas' => Empresa::whereKey($empresaId)->withSum('ventas', 'importe_total')->withSum('gastos', 'importe_total')->with('cuenta')->orderBy('nombre')->get(),
        ]);
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
