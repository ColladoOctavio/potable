<div class="panel p-3">
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Proveedor</th><th>Empresa</th><th>Gastado</th><th>Pagado</th><th>Saldo pendiente</th><th>Saldo a favor</th><th>Gastos</th></tr></thead><tbody>
    @foreach($proveedores as $p)<tr><td>{{ $p->nombre }}</td><td>{{ $p->empresa->nombre }}</td><td>{{ $money($p->total_gastado) }}</td><td>{{ $money($p->total_pagado) }}</td><td>{{ $money($p->saldo_pendiente) }}</td><td>{{ $money($p->saldo_favor) }}</td><td>{{ $p->gastos_cargados }}</td></tr>@endforeach
    </tbody></table></div>
</div>
