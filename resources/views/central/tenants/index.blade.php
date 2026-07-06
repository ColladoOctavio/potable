@extends('layouts.auth')

@section('content')
<section class="card auth-card">
    <div class="card-body p-4">
        <div class="mb-4">
            <img src="{{ asset('brand/potable-logo.svg') }}" alt="PoTable" class="auth-logo mb-3">
            <div class="fs-4 fw-bold">Seleccionar tenant</div>
            <div class="text-muted">Elegí la cuenta con la que querés trabajar.</div>
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
    </div>
</section>
@endsection
