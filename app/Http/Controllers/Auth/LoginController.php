<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Las credenciales no son correctas.'])->onlyInput('email');
        }

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
}
