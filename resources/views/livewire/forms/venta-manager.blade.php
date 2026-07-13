<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))

    <div class="d-flex justify-content-end mb-3">
        <button type="button" wire:click="create" class="btn btn-primary">Nueva venta</button>
    </div>

    @include('livewire.forms.partials.ventas-table')

    @if($showForm)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5">{{ $editingId ? 'Editar venta' : 'Nueva venta' }}</h2>
                        <button type="button" wire:click="closeForm" class="btn-close" aria-label="Cerrar"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Empresa</label><div class="form-control bg-light">{{ $activeEmpresa?->nombre ?? 'Sin empresa activa' }}</div></div>
                                <div class="col-md-4"><label class="form-label">Cliente</label><select wire:model="form.cliente_id" class="form-select"><option value="">Sin cliente</option>@foreach($clientes as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach</select></div>
                                <div class="col-md-4"><label class="form-label">Lote</label><select wire:model="form.lote_id" class="form-select"><option value="">Sin lote</option>@foreach($lotes as $l)<option value="{{ $l->id }}">{{ $l->nombre }}</option>@endforeach</select></div>
                                <div class="col-md-3">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" wire:model="form.fecha" class="form-control @error('form.fecha') is-invalid @enderror" required>
                                    @error('form.fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Descripcion</label>
                                    <input wire:model="form.descripcion" class="form-control @error('form.descripcion') is-invalid @enderror" required>
                                    @error('form.descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Kilos</label>
                                    <input type="number" step="0.01" wire:model.live="form.kilos" class="form-control @error('form.kilos') is-invalid @enderror" required>
                                    @error('form.kilos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Precio/kg</label>
                                    <input type="number" step="0.01" wire:model.live="form.precio_por_kg" class="form-control @error('form.precio_por_kg') is-invalid @enderror" required>
                                    @error('form.precio_por_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4"><label class="form-label">Total</label><div class="form-control bg-light fw-bold">{{ $money($this->total()) }}</div></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" wire:click="closeForm" class="btn btn-outline-secondary">Cancelar</button>
                            <button class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
