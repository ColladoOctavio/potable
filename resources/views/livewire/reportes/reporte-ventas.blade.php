<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="panel p-3 mb-4 row g-3">
        <div class="col-md-3"><select wire:model.live="filtros.cliente_id" class="form-select"><option value="">Cliente</option>@foreach($clientes as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach</select></div>
        <div class="col-md-3"><select wire:model.live="filtros.lote_id" class="form-select"><option value="">Lote</option>@foreach($lotes as $l)<option value="{{ $l->id }}">{{ $l->nombre }}</option>@endforeach</select></div>
        <div class="col-md-3"><input type="date" wire:model.live="filtros.desde" class="form-control"></div>
        <div class="col-md-3"><input type="date" wire:model.live="filtros.hasta" class="form-control"></div>
    </div>
    @include('livewire.reportes.partials.metricas', ['items' => ['Total vendido' => $summary['total'], 'Kilos vendidos' => number_format($summary['kilos'], 0, ',', '.').' kg', 'Precio promedio/kg' => $summary['promedio']]])
    <section class="panel p-3"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Fecha</th><th>Empresa</th><th>Cliente</th><th>Lote</th><th>Total</th></tr></thead><tbody>
        @foreach($ventas as $v)<tr><td>{{ $v->fecha->format('d/m/Y') }}</td><td>{{ $v->empresa->nombre }}</td><td>{{ $v->cliente?->nombre ?? '-' }}</td><td>{{ $v->lote?->nombre ?? '-' }}</td><td>{{ $money($v->importe_total) }}</td></tr>@endforeach
    </tbody></table></div>{{ $ventas->links() }}</section>
</div>
