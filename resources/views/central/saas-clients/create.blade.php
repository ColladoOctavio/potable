@extends('layouts.central')

@section('title', 'Nuevo cliente SaaS')
@section('subtitle', 'Alta de tenant, usuario y empresa inicial')

@section('content')
<form method="POST" action="{{ route('admin.clientes-saas.store') }}" class="panel p-4">
    @csrf

    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Crear cliente</h1>
            <div class="text-muted">El proceso crea la base tenant, corre migraciones y deja una empresa inicial lista.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clientes-saas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary">Crear cliente</button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <h2 class="h6 text-uppercase text-muted mb-3">Tenant</h2>
            <div class="mb-3">
                <label class="form-label" for="tenant_nombre">Nombre del tenant</label>
                <input id="tenant_nombre" name="tenant_nombre" class="form-control @error('tenant_nombre') is-invalid @enderror" value="{{ old('tenant_nombre') }}" required>
                @error('tenant_nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="tenant_slug">Identificador</label>
                <input id="tenant_slug" name="tenant_slug" class="form-control @error('tenant_slug') is-invalid @enderror" value="{{ old('tenant_slug') }}" placeholder="productores-del-sur">
                <div class="form-text">Si lo dejás vacio se genera desde el nombre. Se usa para la URL interna y la base de datos.</div>
                @error('tenant_slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="estado">Estado</label>
                <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror" required>
                    <option value="activo" @selected(old('estado', 'activo') === 'activo')>Activo</option>
                    <option value="prueba" @selected(old('estado') === 'prueba')>Prueba</option>
                </select>
                @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="col-lg-6">
            <h2 class="h6 text-uppercase text-muted mb-3">Usuario cliente</h2>
            <div class="mb-3">
                <label class="form-label" for="owner_name">Nombre</label>
                <input id="owner_name" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name') }}" required>
                @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="owner_email">Email</label>
                <input id="owner_email" type="email" name="owner_email" class="form-control @error('owner_email') is-invalid @enderror" value="{{ old('owner_email') }}" required>
                @error('owner_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="owner_password">Clave</label>
                    <input id="owner_password" type="password" name="owner_password" class="form-control @error('owner_password') is-invalid @enderror" required>
                    @error('owner_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="owner_password_confirmation">Confirmar clave</label>
                    <input id="owner_password_confirmation" type="password" name="owner_password_confirmation" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="col-12">
            <h2 class="h6 text-uppercase text-muted mb-3">Empresa inicial</h2>
            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label" for="empresa_nombre">Nombre</label>
                    <input id="empresa_nombre" name="empresa_nombre" class="form-control @error('empresa_nombre') is-invalid @enderror" value="{{ old('empresa_nombre') }}" required>
                    @error('empresa_nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="empresa_cuit">CUIT</label>
                    <input id="empresa_cuit" name="empresa_cuit" class="form-control @error('empresa_cuit') is-invalid @enderror" value="{{ old('empresa_cuit') }}">
                    @error('empresa_cuit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="empresa_email">Email</label>
                    <input id="empresa_email" type="email" name="empresa_email" class="form-control @error('empresa_email') is-invalid @enderror" value="{{ old('empresa_email') }}">
                    @error('empresa_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-2">
                    <label class="form-label" for="empresa_telefono">Telefono</label>
                    <input id="empresa_telefono" name="empresa_telefono" class="form-control @error('empresa_telefono') is-invalid @enderror" value="{{ old('empresa_telefono') }}">
                    @error('empresa_telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
