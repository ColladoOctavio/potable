<div class="panel p-3">
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Cliente</th><th>Empresa</th><th>Vendido</th><th>Cobrado</th><th>Saldo pendiente</th><th>Saldo a favor</th><th>Ventas</th></tr></thead><tbody>
    @foreach($clientes as $c)<tr><td>{{ $c->nombre }}</td><td>{{ $c->empresa->nombre }}</td><td>{{ $money($c->total_vendido) }}</td><td>{{ $money($c->total_cobrado) }}</td><td>{{ $money($c->saldo_pendiente) }}</td><td>{{ $money($c->saldo_favor) }}</td><td>{{ $c->ventas_cargadas }}</td></tr>@endforeach
    </tbody></table></div>
</div>
