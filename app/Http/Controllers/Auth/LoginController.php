<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureLoginIsNotRateLimited($request);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->loginThrottleKey($request), 60);

            return back()->withErrors(['email' => 'Las credenciales no son correctas.'])->onlyInput('email');
        }

        RateLimiter::clear($this->loginThrottleKey($request));
        $request->session()->regenerate();

        if ($request->user()->isPlatformAdmin()) {
            return redirect()->route('tenants.index');
        }

        $tenants = $request->user()->tenants()->where('estado', '!=', 'suspendido')->get();

        if ($tenants->isEmpty()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Tu cuenta no tiene un cliente activo. Contactá al administrador de PoTable.'])
                ->onlyInput('email');
        }

        if ($tenants->count() === 1) {
            $request->session()->put('tenant_id', $tenants->first()->id);

            return redirect()->route('dashboard');
        }

        return redirect()->route('tenants.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function ensureLoginIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->loginThrottleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->loginThrottleKey($request));

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos. Proba de nuevo en {$seconds} segundos.",
        ]);
    }

    private function loginThrottleKey(Request $request): string
    {
        return Str::lower((string) $request->input('email')).'|'.$request->ip();
    }
}
