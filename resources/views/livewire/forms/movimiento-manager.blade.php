<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Saldo actual</div>
                <div class="metric-value">{{ $money($saldoActual) }}</div>
                <div class="text-muted small">{{ $cuenta?->nombre ?? 'Sin cuenta seleccionada' }}</div>
            </div>
        </div>
        <div class="col-lg-8">
            <form wire:submit="save" class="panel p-3 row g-3">
                <div class="col-md-4"><label class="form-label">Empresa</label><div class="form-control bg-light">{{ $activeEmpresa?->nombre ?? 'Sin empresa activa' }}</div></div>
                <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" wire:model="form.fecha" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Tipo</label><select wire:model.live="form.tipo" class="form-select"><option>ingreso</option><option>egreso</option><option>ajuste</option></select></div>
                <div class="col-md-2"><label class="form-label">Importe</label><input type="number" step="0.01" wire:model="form.importe" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Concepto</label><input wire:model="form.concepto" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Origen</label><select wire:model="form.origen_id" class="form-select"><option value="">Manual</option>@if($form['tipo'] === 'ingreso')@foreach($ventas as $v)<option value="{{ $v->id }}">Venta #{{ $v->id }} - {{ $v->descripcion }}</option>@endforeach @elseif($form['tipo'] === 'egreso')@foreach($gastos as $g)<option value="{{ $g->id }}">Gasto #{{ $g->id }} - {{ $g->descripcion }}</option>@endforeach @endif</select></div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Registrar</button></div>
            </form>
        </div>
    </div>
    <section class="panel p-3">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Fecha</th><th>Empresa</th><th>Tipo</th><th>Concepto</th><th>Importe</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @forelse($movimientos as $mov)
                    <tr><td>{{ $mov->fecha->format('d/m/Y') }}</td><td>{{ $mov->empresa->nombre }}</td><td><span class="badge text-bg-secondary status-badge">{{ $mov->tipo }}</span></td><td>{{ $mov->concepto }}</td><td>{{ $money($mov->importe) }}</td><td class="text-end"><button wire:click="delete({{ $mov->id }})" wire:confirm="Confirmar eliminacion" class="btn btn-sm btn-outline-danger">Eliminar</button></td></tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">No hay movimientos.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $movimientos->links() }}
    </section>
</div>
