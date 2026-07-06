@extends('layouts.auth')

@section('content')
<section class="card auth-card">
    <div class="card-body p-4">
        <div class="mb-4">
            <img src="{{ asset('brand/potable-logo.svg') }}" alt="PoTable" class="auth-logo mb-3">
            <div class="text-muted">Ingreso central al SaaS</div>
        </div>
        <form method="POST" action="{{ route('login.store') }}" class="vstack gap-3">
            @csrf
            <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', 'admin@potable.test') }}" class="form-control" required autofocus>
            </div>
            <div>
                <label class="form-label">Password</label>
                <input type="password" name="password" value="password" class="form-control" required>
            </div>
            <label class="form-check">
                <input type="checkbox" name="remember" class="form-check-input">
                <span class="form-check-label">Recordarme</span>
            </label>
            @if($errors->any())
                <div class="alert alert-danger mb-0">{{ $errors->first() }}</div>
            @endif
            <button class="btn btn-primary w-100">Entrar</button>
        </form>
    </div>
</section>
@endsection
