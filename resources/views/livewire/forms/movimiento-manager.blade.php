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
            <div class="panel p-3 h-100 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 mb-1">Cuenta</h2>
                    <div class="text-muted">{{ $activeEmpresa?->nombre ?? 'Sin empresa activa' }}</div>
                </div>
                <button type="button" wire:click="create" class="btn btn-primary">Nuevo movimiento</button>
            </div>
        </div>
    </div>
    <section class="panel p-3">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Fecha</th><th>Empresa</th><th>Tipo</th><th>Origen</th><th>Concepto</th><th>Importe</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @forelse($movimientos as $mov)
                    <tr><td>{{ $mov->fecha->format('d/m/Y') }}</td><td>{{ $mov->empresa->nombre }}</td><td><span class="badge text-bg-secondary status-badge">{{ $mov->tipo }}</span></td><td>{{ $mov->origen?->nombre ?? '-' }}</td><td>{{ $mov->concepto }}</td><td>{{ $money($mov->importe) }}</td><td class="text-end"><button wire:click="delete({{ $mov->id }})" wire:confirm="Confirmar eliminacion" class="btn btn-sm btn-outline-danger">Eliminar</button></td></tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state">No hay movimientos.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $movimientos->links() }}
    </section>

    @if($showForm)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5">Nuevo movimiento</h2>
                        <button type="button" wire:click="closeForm" class="btn-close" aria-label="Cerrar"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Empresa</label><div class="form-control bg-light">{{ $activeEmpresa?->nombre ?? 'Sin empresa activa' }}</div></div>
                                <div class="col-md-3">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" wire:model="form.fecha" class="form-control @error('form.fecha') is-invalid @enderror" required>
                                    @error('form.fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tipo</label>
                                    <select wire:model.live="form.tipo" class="form-select @error('form.tipo') is-invalid @enderror" required><option>ingreso</option><option>egreso</option><option>ajuste</option></select>
                                    @error('form.tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Importe</label>
                                    <input type="number" step="0.01" wire:model="form.importe" class="form-control @error('form.importe') is-invalid @enderror" required>
                                    @error('form.importe')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Concepto</label>
                                    <input wire:model="form.concepto" class="form-control @error('form.concepto') is-invalid @enderror" required>
                                    @error('form.concepto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                @if($form['tipo'] !== 'ajuste')
                                    <div class="col-md-4">
                                        <label class="form-label">Origen</label>
                                        <select wire:model="form.origen_id" class="form-select @error('form.origen_id') is-invalid @enderror" required>
                                            <option value="">{{ $form['tipo'] === 'ingreso' ? 'Seleccionar cliente' : 'Seleccionar proveedor' }}</option>
                                            @if($form['tipo'] === 'ingreso')
                                                @foreach($clientes as $cliente)<option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>@endforeach
                                            @elseif($form['tipo'] === 'egreso')
                                                @foreach($proveedores as $proveedor)<option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>@endforeach
                                            @endif
                                        </select>
                                        @error('form.origen_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" wire:click="closeForm" class="btn btn-outline-secondary">Cancelar</button>
                            <button class="btn btn-primary">Registrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
