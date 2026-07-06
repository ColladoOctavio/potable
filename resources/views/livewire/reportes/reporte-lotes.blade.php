<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="panel p-3 mb-4 row g-3">
        <div class="col-md-3"><select wire:model.live="filtros.lote_id" class="form-select"><option value="">Lote</option>@foreach($lotesFiltro as $l)<option value="{{ $l->id }}">{{ $l->nombre }}</option>@endforeach</select></div>
        <div class="col-md-3"><input type="date" wire:model.live="filtros.desde" class="form-control"></div>
        <div class="col-md-3"><input type="date" wire:model.live="filtros.hasta" class="form-control"></div>
    </div>
    <section class="panel p-3"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Lote</th><th>Empresa</th><th>Ha</th><th>Ventas</th><th>Gastos</th><th>Resultado</th><th>Costo/ha</th><th>Kg/ha</th></tr></thead><tbody>
        @foreach($lotes as $l)<tr><td>{{ $l->nombre }}</td><td>{{ $l->empresa->nombre }}</td><td>{{ number_format($l->hectareas, 2, ',', '.') }}</td><td>{{ $money($l->total_ventas) }}</td><td>{{ $money($l->total_gastos) }}</td><td>{{ $money($l->resultado) }}</td><td>{{ $money($l->costo_hectarea) }}</td><td>{{ number_format($l->kilos_hectarea, 0, ',', '.') }}</td></tr>@endforeach
    </tbody></table></div></section>
</div>
