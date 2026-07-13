@extends('layouts.app')

@section('title', ($item ? 'Editar ' : 'Crear ').$config['singular'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">{{ $item ? 'Editar' : 'Crear' }} {{ strtolower($config['singular']) }}</h1>
    <a href="{{ route($config['route'].'.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<form method="POST" action="{{ $item ? route($config['route'].'.update', $item) : route($config['route'].'.store') }}" class="panel p-4">
    @csrf
    @if($item)
        @method('PUT')
    @endif
    <div class="row g-3">
        @foreach($config['fields'] as $name => $field)
            @php
                $value = old($name, data_get($item, $name, $field['default'] ?? null));
            @endphp
            <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                <label class="form-label">{{ $field['label'] }}</label>
                @if($field['type'] === 'textarea')
                    <textarea name="{{ $name }}" class="form-control @error($name) is-invalid @enderror" rows="3" @required($field['required'] ?? false)>{{ $value }}</textarea>
                @elseif($field['type'] === 'empresa')
                    <input type="hidden" name="{{ $name }}" value="{{ $activeEmpresa?->id }}">
                    <div class="form-control bg-light">{{ $activeEmpresa?->nombre ?? 'Sin empresa activa' }}</div>
                @else
                    <input type="{{ $field['type'] }}" step="{{ $field['step'] ?? '' }}" name="{{ $name }}" value="{{ $value }}" class="form-control @error($name) is-invalid @enderror" @required($field['required'] ?? false)>
                @endif
                @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        @endforeach
    </div>
    <div class="mt-4">
        <button class="btn btn-primary">Guardar</button>
    </div>
</form>
@endsection
