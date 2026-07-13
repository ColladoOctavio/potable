@extends('layouts.central')

@section('title', 'Clientes SaaS')
@section('subtitle', 'Tenants, usuarios propietarios y bases tenant')

@section('content')
<section class="panel p-4">
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Clientes creados</h1>
            <div class="text-muted">Cada fila representa un tenant con su usuario principal.</div>
        </div>
        <a href="{{ route('admin.clientes-saas.create') }}" class="btn btn-primary">Nuevo cliente</a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Usuario principal</th>
                    <th>Base tenant</th>
                    <th>Estado</th>
                    <th>Usuarios</th>
                    <th>Alta</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $tenant->nombre }}</div>
                            <div class="small text-muted">{{ $tenant->id }}</div>
                        </td>
                        <td>
                            @if($tenant->owner)
                                <div>{{ $tenant->owner->name }}</div>
                                <div class="small text-muted">{{ $tenant->owner->email }}</div>
                            @else
                                <span class="text-muted">Sin propietario</span>
                            @endif
                        </td>
                        <td><code>{{ $tenant->database_name }}</code></td>
                        <td>
                            @php
                                $estadoClass = match ($tenant->estado) {
                                    'activo' => 'text-bg-success',
                                    'prueba' => 'text-bg-warning',
                                    'suspendido' => 'text-bg-danger',
                                    default => 'text-bg-light',
                                };
                            @endphp
                            <span class="badge {{ $estadoClass }}">{{ $tenant->estado }}</span>
                        </td>
                        <td>{{ $tenant->users_count }}</td>
                        <td>{{ $tenant->created_at?->format('d/m/Y') }}</td>
                        <td class="text-end">
                            @if($tenant->estado === 'suspendido')
                                <form method="POST" action="{{ route('admin.clientes-saas.activate', $tenant) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-outline-success btn-sm">Habilitar</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.clientes-saas.suspend', $tenant) }}" onsubmit="return confirm('Este cliente no podrá acceder a PoTable hasta que lo vuelvas a habilitar. ¿Continuar?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-outline-danger btn-sm">Inhabilitar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">Todavia no hay clientes cargados.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $tenants->links() }}
    </div>
</section>
@endsection
