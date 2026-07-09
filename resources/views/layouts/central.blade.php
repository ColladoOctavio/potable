<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PoTable')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/potable-mark.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/potable.css') }}" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar p-3">
        <a href="{{ route('tenants.index') }}" class="brand-lockup mb-3" aria-label="PoTable">
            <span class="brand-mark"><img src="{{ asset('brand/potable-mark.svg') }}" alt=""></span>
            <span>
                <span class="brand-name"><span>Po</span>Table</span>
                <span class="brand-subtitle">Administracion SaaS</span>
            </span>
        </a>
        <nav class="nav flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('tenants.index') ? 'active' : '' }}" href="{{ route('tenants.index') }}">Tenants</a>
            @if(auth()->user()->isPlatformAdmin())
                <a class="nav-link {{ request()->routeIs('admin.clientes-saas.*') ? 'active' : '' }}" href="{{ route('admin.clientes-saas.index') }}">Clientes SaaS</a>
            @endif
        </nav>
    </aside>
    <div class="main-area">
        <header class="topbar px-4 py-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div>
                <div class="fw-bold">@yield('title', 'PoTable')</div>
                <div class="text-muted small">@yield('subtitle', 'Panel central')</div>
            </div>
            <div class="d-flex align-items-center gap-2">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
