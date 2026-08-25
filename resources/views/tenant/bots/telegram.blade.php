@extends('layouts.app')

@section('title', 'Bot Telegram')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">Bot Telegram</h1>
        <div class="text-muted">Empresa activa: {{ \App\Models\Tenant\Empresa::find(session('empresa_id'))?->nombre }}.</div>
    </div>
    @unless($linkingDisabledForAdmin)
        <form method="POST" action="{{ route('bots.telegram.codigo.store') }}">
            @csrf
            <button class="btn btn-primary">Generar codigo</button>
        </form>
    @endunless
</div>

<section class="panel p-4">
    @if(session('bot_link_error'))
        <div class="alert alert-danger mb-4">{{ session('bot_link_error') }}</div>
    @endif

    @if($linkingDisabledForAdmin)
        <div class="alert alert-warning mb-4">
            Estás usando una cuenta administradora de PoTable. Para vincular Telegram, ingresá con la cuenta del cliente que va a usar el bot.
        </div>
    @endif

    @if($activeLink)
        <div class="alert alert-success mb-4">
            Vinculado con Telegram: {{ $activeLink->external_display_name ?? $activeLink->external_username ?? $activeLink->external_user_id }}.
        </div>
    @else
        <div class="alert alert-secondary mb-4">
            Tu cuenta de Potable no tiene Telegram vinculado para esta empresa.
        </div>
    @endif

    @if($otherActiveLinks->isNotEmpty())
        <div class="alert alert-info mb-4">
            <div class="fw-semibold mb-2">Vinculaciones activas de otros usuarios en esta empresa:</div>
            <ul class="mb-0">
                @foreach($otherActiveLinks as $link)
                    <li>
                        Telegram: {{ $link->external_display_name ?? $link->external_username ?? $link->external_user_id }}
                        · Cuenta Potable: {{ $link->user?->email ?? 'usuario #'.$link->user_id }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($generatedCode)
        <div class="row g-3 align-items-stretch">
            <div class="col-lg-4">
                <div class="metric-card p-3 h-100">
                    <div class="metric-label">Codigo</div>
                    <div class="metric-value">{{ $generatedCode }}</div>
                    @if($expiresAt)
                        <div class="text-muted small mt-2">Vence {{ $expiresAt }}.</div>
                    @endif
                </div>
            </div>
            <div class="col-lg-8">
                <label class="form-label">Mensaje para Telegram</label>
                <input class="form-control form-control-lg" readonly value="/vincular {{ $generatedCode }}">
                <div class="form-text">Este codigo vincula Telegram con la empresa activa. Despues podes cambiarla desde el chat con /empresa.</div>
            </div>
        </div>
    @else
        <div class="empty-state">No hay codigo activo.</div>
    @endif
</section>
@endsection
