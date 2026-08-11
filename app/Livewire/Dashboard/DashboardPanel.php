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
        $ingresosCuenta = (float) (clone $movimientos)->where('tipo', 'ingreso')->sum('importe');
        $egresosCuenta = (float) (clone $movimientos)->where('tipo', 'egreso')->sum('importe');
        $totalCobrado = (float) MovimientoCuenta::where('empresa_id', $empresaId)->where('tipo', 'ingreso')->where('origen_type', Cliente::class)->sum('importe');
        $totalPagado = (float) MovimientoCuenta::where('empresa_id', $empresaId)->where('tipo', 'egreso')->where('origen_type', Proveedor::class)->sum('importe');
        $saldoInicial = (float) (clone $cuentas)->sum('saldo_inicial');
        $ajustes = (float) (clone $movimientos)->where('tipo', 'ajuste')->sum('importe');
        $clientesConSaldo = Cliente::where('empresa_id', $empresaId)->get()->map(function (Cliente $cliente) {
            $ventas = (float) Venta::where('cliente_id', $cliente->id)->sum('importe_total');
            $cobros = (float) MovimientoCuenta::where('origen_type', Cliente::class)->where('origen_id', $cliente->id)->where('tipo', 'ingreso')->sum('importe');

            return (float) $cliente->saldo_inicial + $cobros - $ventas;
        });
        $proveedoresConSaldo = Proveedor::where('empresa_id', $empresaId)->get()->map(function (Proveedor $proveedor) {
            $gastos = (float) Gasto::where('proveedor_id', $proveedor->id)->sum('importe_total');
            $pagos = (float) MovimientoCuenta::where('origen_type', Proveedor::class)->where('origen_id', $proveedor->id)->where('tipo', 'egreso')->sum('importe');

            return (float) $proveedor->saldo_inicial + $gastos - $pagos;
        });
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
                'pendiente_cobro' => $clientesConSaldo->filter(fn (float $saldo) => $saldo < 0)->sum(fn (float $saldo) => abs($saldo)),
                'gastos' => $totalGastos,
                'pagado' => $totalPagado,
                'pendiente_pago' => $proveedoresConSaldo->filter(fn (float $saldo) => $saldo > 0)->sum(),
                'resultado' => $totalVentas - $totalGastos,
                'saldo' => $saldoInicial + $ingresosCuenta - $egresosCuenta + $ajustes,
                'bolsas' => (float) (clone $ventas)->sum('bolsas'),
                'costo_hectarea' => $hectareas > 0 ? $totalGastos / $hectareas : 0,
                'clientes_deuda' => $clientesConSaldo->filter(fn (float $saldo) => $saldo < 0)->count(),
                'proveedores_deuda' => $proveedoresConSaldo->filter(fn (float $saldo) => $saldo > 0)->count(),
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
