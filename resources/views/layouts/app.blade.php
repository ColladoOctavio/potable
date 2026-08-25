<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PoTable')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/potable-mark.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.potable-styles')
    @livewireStyles
</head>
<body>
@php
    $tenantActivo = session('tenant_id') ? \App\Models\Central\Tenant::find(session('tenant_id')) : null;
    $empresasTenant = \App\Models\Tenant\Empresa::orderBy('nombre')->get();
    $empresaActiva = $empresasTenant->firstWhere('id', session('empresa_id'));
    $nav = [
        ['route' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'empresas.index', 'label' => 'Empresas'],
        ['route' => 'lotes.index', 'label' => 'Lotes / Campos'],
        ['route' => 'clientes.index', 'label' => 'Clientes'],
        ['route' => 'proveedores.index', 'label' => 'Proveedores'],
        ['route' => 'categorias-gastos.index', 'label' => 'Categorias'],
        ['route' => 'gastos.index', 'label' => 'Gastos'],
        ['route' => 'ventas.index', 'label' => 'Ventas'],
        ['route' => 'cuenta.show', 'label' => 'Cuenta'],
        ['route' => 'bots.telegram.index', 'label' => 'Bot'],
    ];
    $reportes = [
        ['route' => 'reportes.general', 'label' => 'General'],
        ['route' => 'reportes.ventas', 'label' => 'Ventas'],
        ['route' => 'reportes.gastos', 'label' => 'Gastos'],
        ['route' => 'reportes.clientes', 'label' => 'Clientes'],
        ['route' => 'reportes.proveedores', 'label' => 'Proveedores'],
        ['route' => 'reportes.lotes', 'label' => 'Lotes'],
    ];
    $groupIsActive = function (array $items): bool {
        foreach ($items as $item) {
            if (request()->routeIs($item['route'])) {
                return true;
            }
        }

        return false;
    };
@endphp
<div class="app-shell">
    <aside class="sidebar p-3">
        <a href="{{ route('dashboard') }}" class="brand-lockup mb-3" aria-label="PoTable dashboard">
            <span class="brand-mark"><img src="{{ asset('brand/potable-mark.svg') }}" alt=""></span>
            <span>
                <span class="brand-name"><span>Po</span>Table</span>
                <span class="brand-subtitle">Gestion contable papera</span>
            </span>
        </a>
        <nav class="nav flex-column gap-1">
            <details class="sidebar-group" {{ $groupIsActive($nav) ? 'open' : '' }}>
                <summary class="sidebar-group-toggle">
                    <span>Gestion</span>
                    <span class="sidebar-group-chevron" aria-hidden="true">›</span>
                </summary>
                <div class="sidebar-group-items">
                    @foreach($nav as $item)
                        <a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </details>
            <details class="sidebar-group" {{ $groupIsActive($reportes) ? 'open' : '' }}>
                <summary class="sidebar-group-toggle">
                    <span>Reportes</span>
                    <span class="sidebar-group-chevron" aria-hidden="true">›</span>
                </summary>
                <div class="sidebar-group-items">
                    @foreach($reportes as $item)
                        <a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </details>
        </nav>
    </aside>
    <div class="main-area">
        <header class="topbar px-4 py-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div>
                <div class="fw-bold">@yield('title', 'PoTable')</div>
                <div class="text-muted small">
                    {{ $tenantActivo?->nombre ?? 'Sin tenant activo' }}
                    @if($empresaActiva)
                        · {{ $empresaActiva->nombre }}
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($empresasTenant->count())
                    <form method="POST" action="{{ route('empresa-activa.update') }}" class="empresa-switcher">
                        @csrf
                        <label class="visually-hidden" for="empresa_id">Empresa activa</label>
                        <select id="empresa_id" name="empresa_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($empresasTenant as $empresa)
                                <option value="{{ $empresa->id }}" @selected($empresaActiva?->id === $empresa->id)>{{ $empresa->nombre }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span class="badge text-bg-warning">Sin empresas</span>
                @endif
                @if(auth()->user()->isPlatformAdmin())
                    <a href="{{ route('admin.clientes-saas.index') }}" class="btn btn-outline-secondary btn-sm">Clientes SaaS</a>
                    <a href="{{ route('tenants.index') }}" class="btn btn-outline-secondary btn-sm">Cambiar tenant</a>
                @endif
                <span class="small text-muted">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm">Salir</button>
                </form>
            </div>
        </header>
        <main class="content-wrap">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@livewireScripts
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
