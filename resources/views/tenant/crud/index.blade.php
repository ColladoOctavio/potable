@extends('layouts.app')

@section('title', $config['title'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $config['title'] }}</h1>
        <div class="text-muted">
            Administracion de {{ strtolower($config['title']) }}.
            @if($activeEmpresa && ($config['route'] ?? '') !== 'empresas')
                Empresa activa: {{ $activeEmpresa->nombre }}.
            @endif
        </div>
    </div>
    <a href="{{ route($config['route'].'.create') }}" class="btn btn-primary">Crear</a>
</div>

<section class="panel p-3">
    @if($items->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    @foreach($config['columns'] as $column)
                        <th>{{ ucfirst(str_replace(['_', '.'], ' ', $column)) }}</th>
                    @endforeach
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr>
                        @foreach($config['columns'] as $column)
                            <td>{{ data_get($item, $column) }}</td>
                        @endforeach
                        <td class="text-end">
                            <a href="{{ route($config['route'].'.edit', $item) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            <form method="POST" action="{{ route($config['route'].'.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Confirmar eliminacion')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    @else
        <div class="empty-state">No hay registros todavia.</div>
    @endif
</section>
@endsection
