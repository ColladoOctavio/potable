<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="panel p-4 mb-4">
        <h2 class="h5 mb-3">{{ $editingId ? 'Editar gasto' : 'Nuevo gasto' }}</h2>
        <form wire:submit="save" class="row g-3">
            <div class="col-md-3"><label class="form-label">Empresa</label><div class="form-control bg-light">{{ $activeEmpresa?->nombre ?? 'Sin empresa activa' }}</div></div>
            <div class="col-md-3"><label class="form-label">Proveedor</label><select wire:model="form.proveedor_id" class="form-select"><option value="">Sin proveedor</option>@foreach($proveedores as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Categoria</label><select wire:model="form.categoria_gasto_id" class="form-select"><option value="">Seleccionar</option>@foreach($categorias as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Lote</label><select wire:model="form.lote_id" class="form-select"><option value="">Sin lote</option>@foreach($lotes as $l)<option value="{{ $l->id }}">{{ $l->nombre }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" wire:model="form.fecha" class="form-control"></div>
            <div class="col-md-5"><label class="form-label">Descripcion</label><input wire:model="form.descripcion" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Importe</label><input type="number" step="0.01" wire:model="form.importe_total" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Estado</label><select wire:model="form.estado_pago" class="form-select"><option>pendiente</option><option>parcial</option><option>pagado</option></select></div>
            <div class="col-12"><button class="btn btn-primary">Guardar</button><button type="button" wire:click="resetForm" class="btn btn-outline-secondary ms-2">Limpiar</button></div>
        </form>
    </div>
    @include('livewire.forms.partials.gastos-table')
</div>
