<?php

namespace App\Livewire\Dashboard;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Cuenta;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\MovimientoCuenta;
use App\Models\Tenant\Proveedor;
use App\Models\Tenant\Venta;
use Livewire\Component;

class DashboardPanel extends Component
{
    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $ventas = Venta::query()->where('empresa_id', $empresaId);
        $gastos = Gasto::query()->where('empresa_id', $empresaId);
        $movimientos = MovimientoCuenta::query()->where('empresa_id', $empresaId);
        $cuentas = Cuenta::query()->where('empresa_id', $empresaId);

        $totalVentas = (float) (clone $ventas)->sum('importe_total');
        $totalGastos = (float) (clone $gastos)->sum('importe_total');
        $totalCobrado = (float) (clone $movimientos)->where('tipo', 'ingreso')->sum('importe');
        $totalPagado = (float) (clone $movimientos)->where('tipo', 'egreso')->sum('importe');
        $saldoInicial = (float) (clone $cuentas)->sum('saldo_inicial');
        $ajustes = (float) (clone $movimientos)->where('tipo', 'ajuste')->sum('importe');
        $hectareas = (float) Empresa::query()
            ->when($empresaId, fn ($q) => $q->whereKey($empresaId))
            ->withSum('lotes', 'hectareas')
            ->get()
            ->sum('lotes_sum_hectareas');

        return view('livewire.dashboard.dashboard-panel', [
            'activeEmpresa' => Empresa::find($empresaId),
            'metrics' => [
                'ventas' => $totalVentas,
                'cobrado' => $totalCobrado,
                'pendiente_cobro' => max($totalVentas - $totalCobrado, 0),
                'gastos' => $totalGastos,
                'pagado' => $totalPagado,
                'pendiente_pago' => max($totalGastos - $totalPagado, 0),
                'resultado' => $totalVentas - $totalGastos,
                'saldo' => $saldoInicial + $totalCobrado - $totalPagado + $ajustes,
                'kilos' => (float) (clone $ventas)->sum('kilos'),
                'costo_hectarea' => $hectareas > 0 ? $totalGastos / $hectareas : 0,
                'clientes_deuda' => Cliente::query()->where('empresa_id', $empresaId)->whereHas('ventas', fn ($q) => $q->whereIn('estado_cobro', ['pendiente', 'parcial']))->count(),
                'proveedores_deuda' => Proveedor::query()->where('empresa_id', $empresaId)->whereHas('gastos', fn ($q) => $q->whereIn('estado_pago', ['pendiente', 'parcial']))->count(),
            ],
            'ultimasVentas' => (clone $ventas)->with(['cliente', 'lote'])->latest('fecha')->limit(6)->get(),
            'ultimosGastos' => (clone $gastos)->with(['proveedor', 'categoriaGasto'])->latest('fecha')->limit(6)->get(),
            'ultimosMovimientos' => (clone $movimientos)->with('empresa')->latest('fecha')->limit(6)->get(),
            'gastosPorCategoria' => Gasto::query()
                ->selectRaw('categoria_gasto_id, sum(importe_total) as total')
                ->where('empresa_id', $empresaId)
                ->with('categoriaGasto')
                ->groupBy('categoria_gasto_id')
                ->orderByDesc('total')
                ->limit(6)
                ->get(),
            'ventasPorCliente' => Venta::query()
                ->selectRaw('cliente_id, sum(importe_total) as total')
                ->where('empresa_id', $empresaId)
                ->with('cliente')
                ->groupBy('cliente_id')
                ->orderByDesc('total')
                ->limit(6)
                ->get(),
            'resultadoPorLote' => Venta::query()
                ->selectRaw('lote_id, sum(importe_total) as total_ventas')
                ->where('empresa_id', $empresaId)
                ->with('lote')
                ->groupBy('lote_id')
                ->limit(6)
                ->get()
                ->map(function ($row) use ($empresaId) {
                    $gastos = Gasto::query()
                        ->where('empresa_id', $empresaId)
                        ->where('lote_id', $row->lote_id)
                        ->sum('importe_total');

                    $row->resultado = $row->total_ventas - $gastos;

                    return $row;
                }),
        ]);
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
