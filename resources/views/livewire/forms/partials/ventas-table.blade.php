<section class="panel p-3">
    @if($ventas->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Fecha</th><th>Empresa</th><th>Cliente</th><th>Descripcion</th><th>Bolsas</th><th>Total</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($ventas as $venta)
                    <tr>
                        <td>{{ $venta->fecha->format('d/m/Y') }}</td>
                        <td>{{ $venta->empresa->nombre }}</td>
                        <td>{{ $venta->cliente?->nombre ?? '-' }}</td>
                        <td>{{ $venta->descripcion }}</td>
                        <td>{{ number_format($venta->bolsas, 0, ',', '.') }}</td>
                        <td>{{ $money($venta->importe_total) }}</td>
                        <td class="text-end"><button wire:click="edit({{ $venta->id }})" class="btn btn-sm btn-outline-primary">Editar</button><button wire:click="delete({{ $venta->id }})" wire:confirm="Confirmar eliminacion" class="btn btn-sm btn-outline-danger">Eliminar</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $ventas->links() }}
    @else
        <div class="empty-state">No hay ventas cargadas.</div>
    @endif
</section>
