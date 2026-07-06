<section class="panel p-3">
    @if($gastos->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Fecha</th><th>Empresa</th><th>Proveedor</th><th>Categoria</th><th>Descripcion</th><th>Total</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($gastos as $gasto)
                    <tr>
                        <td>{{ $gasto->fecha->format('d/m/Y') }}</td>
                        <td>{{ $gasto->empresa->nombre }}</td>
                        <td>{{ $gasto->proveedor?->nombre ?? '-' }}</td>
                        <td>{{ $gasto->categoriaGasto?->nombre ?? '-' }}</td>
                        <td>{{ $gasto->descripcion }}</td>
                        <td>{{ $money($gasto->importe_total) }}</td>
                        <td><span class="badge text-bg-warning status-badge">{{ $gasto->estado_pago }}</span></td>
                        <td class="text-end"><button wire:click="edit({{ $gasto->id }})" class="btn btn-sm btn-outline-primary">Editar</button><button wire:click="delete({{ $gasto->id }})" wire:confirm="Confirmar eliminacion" class="btn btn-sm btn-outline-danger">Eliminar</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $gastos->links() }}
    @else
        <div class="empty-state">No hay gastos cargados.</div>
    @endif
</section>
