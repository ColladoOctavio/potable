<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="panel p-3 mb-4 row g-3">
        <div class="col-md-2"><select wire:model.live="filtros.proveedor_id" class="form-select"><option value="">Proveedor</option>@foreach($proveedores as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach</select></div>
        <div class="col-md-2"><select wire:model.live="filtros.categoria_gasto_id" class="form-select"><option value="">Categoria</option>@foreach($categorias as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach</select></div>
        <div class="col-md-2"><select wire:model.live="filtros.lote_id" class="form-select"><option value="">Lote</option>@foreach($lotes as $l)<option value="{{ $l->id }}">{{ $l->nombre }}</option>@endforeach</select></div>
        <div class="col-md-2"><select wire:model.live="filtros.estado_pago" class="form-select"><option value="">Estado</option><option>pendiente</option><option>parcial</option><option>pagado</option></select></div>
        <div class="col-md-2"><input type="date" wire:model.live="filtros.desde" class="form-control"></div>
        <div class="col-md-2"><input type="date" wire:model.live="filtros.hasta" class="form-control"></div>
    </div>
    @include('livewire.reportes.partials.metricas', ['items' => ['Total gastos' => $summary['total'], 'Total pagado' => $summary['pagado'], 'Pendiente' => $summary['pendiente']]])
    <div class="row g-3">
        <div class="col-lg-8"><section class="panel p-3"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Fecha</th><th>Empresa</th><th>Proveedor</th><th>Categoria</th><th>Total</th><th>Estado</th></tr></thead><tbody>@foreach($gastos as $g)<tr><td>{{ $g->fecha->format('d/m/Y') }}</td><td>{{ $g->empresa->nombre }}</td><td>{{ $g->proveedor?->nombre ?? '-' }}</td><td>{{ $g->categoriaGasto?->nombre ?? '-' }}</td><td>{{ $money($g->importe_total) }}</td><td><span class="badge text-bg-warning status-badge">{{ $g->estado_pago }}</span></td></tr>@endforeach</tbody></table></div>{{ $gastos->links() }}</section></div>
        <div class="col-lg-4"><section class="panel p-3"><h2 class="h6">Resumen por categoria</h2>@foreach($porCategoria as $row)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $row->categoriaGasto?->nombre }}</span><strong>{{ $money($row->total) }}</strong></div>@endforeach</section></div>
    </div>
</div>
