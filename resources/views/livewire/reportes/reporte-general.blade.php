<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    @include('livewire.reportes.partials.metricas', ['items' => [
        'Total ventas' => $summary['ventas'],
        'Total gastos' => $summary['gastos'],
        'Resultado' => $summary['resultado'],
        'Saldo cuenta' => $summary['saldo'],
        'Pendiente cobro' => $summary['pendiente_cobro'],
        'Pendiente pago' => $summary['pendiente_pago'],
    ]])
    <section class="panel p-3">
        <h2 class="h5">Resumen por empresa</h2>
        <div class="table-responsive">
            <table class="table table-hover"><thead><tr><th>Empresa</th><th>Ventas</th><th>Gastos</th><th>Resultado</th></tr></thead><tbody>
                @foreach($empresas as $empresa)
                    <tr><td>{{ $empresa->nombre }}</td><td>{{ $money($empresa->ventas_sum_importe_total) }}</td><td>{{ $money($empresa->gastos_sum_importe_total) }}</td><td>{{ $money(($empresa->ventas_sum_importe_total ?? 0) - ($empresa->gastos_sum_importe_total ?? 0)) }}</td></tr>
                @endforeach
            </tbody></table>
        </div>
    </section>
</div>
