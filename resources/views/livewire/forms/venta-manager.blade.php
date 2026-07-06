<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="panel p-4 mb-4">
        <h2 class="h5 mb-3">{{ $editingId ? 'Editar venta' : 'Nueva venta' }}</h2>
        <form wire:submit="save" class="row g-3">
            <div class="col-md-4"><label class="form-label">Empresa</label><div class="form-control bg-light">{{ $activeEmpresa?->nombre ?? 'Sin empresa activa' }}</div></div>
            <div class="col-md-4"><label class="form-label">Cliente</label><select wire:model="form.cliente_id" class="form-select"><option value="">Sin cliente</option>@foreach($clientes as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Lote</label><select wire:model="form.lote_id" class="form-select"><option value="">Sin lote</option>@foreach($lotes as $l)<option value="{{ $l->id }}">{{ $l->nombre }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" wire:model="form.fecha" class="form-control"></div>
            <div class="col-md-5"><label class="form-label">Descripcion</label><input wire:model="form.descripcion" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Kilos</label><input type="number" step="0.01" wire:model.live="form.kilos" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Precio/kg</label><input type="number" step="0.01" wire:model.live="form.precio_por_kg" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Estado</label><select wire:model="form.estado_cobro" class="form-select"><option>pendiente</option><option>parcial</option><option>cobrada</option></select></div>
            <div class="col-md-4"><label class="form-label">Total</label><div class="form-control bg-light fw-bold">{{ $money($this->total()) }}</div></div>
            <div class="col-md-4 d-flex align-items-end gap-2"><button class="btn btn-primary">Guardar</button><button type="button" wire:click="resetForm" class="btn btn-outline-secondary">Limpiar</button></div>
        </form>
    </div>
    @include('livewire.forms.partials.ventas-table')
</div>
