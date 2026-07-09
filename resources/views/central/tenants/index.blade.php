@extends('layouts.central')

@section('title', 'Seleccionar tenant')
@section('subtitle', 'Elegí la cuenta con la que querés trabajar')

@section('content')
<section class="panel p-4">
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Seleccionar tenant</h1>
            <div class="text-muted">Entrá a la cuenta que querés administrar.</div>
        </div>
        @if(auth()->user()->isPlatformAdmin())
            <a href="{{ route('admin.clientes-saas.create') }}" class="btn btn-primary">Nuevo cliente</a>
        @endif
    </div>

    <div class="vstack gap-3">
        @forelse($tenants as $tenant)
            <form method="POST" action="{{ route('tenants.seleccionar', $tenant) }}">
                @csrf
                <button class="btn btn-outline-primary w-100 text-start">
                    <span class="fw-semibold">{{ $tenant->nombre }}</span>
                    <span class="badge text-bg-light float-end">{{ $tenant->estado }}</span>
                    <span class="d-block small text-muted">{{ $tenant->database_name }}</span>
                </button>
            </form>
        @empty
            <div class="empty-state">Tu usuario no tiene tenants activos.</div>
        @endforelse
    </div>
</section>
@endsection
