<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantSelectionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->isPlatformAdmin()) {
            $tenant = $this->firstClientTenant($request->user());

            if ($tenant) {
                $request->session()->put('tenant_id', $tenant->id);

                return redirect()->route('dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Tu cuenta no tiene un cliente activo. Contactá al administrador de PoTable.']);
        }

        $tenants = $this->availableTenantsQuery($request->user())->orderBy('nombre')->get();

        return view('central.tenants.index', compact('tenants'));
    }

    public function seleccionar(Request $request, string $tenant): RedirectResponse
    {
        if (! $request->user()->isPlatformAdmin()) {
            $tenantModel = $this->firstClientTenant($request->user());
            abort_unless($tenantModel?->getKey() === $tenant, 404);

            $request->session()->put('tenant_id', $tenantModel->id);
            $request->session()->forget('empresa_id');

            return redirect()->route('dashboard')->with('success', 'Tenant activo: '.$tenantModel->nombre);
        }

        $tenantModel = $this->availableTenantsQuery($request->user())->whereKey($tenant)->where('estado', '!=', 'suspendido')->firstOrFail();

        $request->session()->put('tenant_id', $tenantModel->id);
        $request->session()->forget('empresa_id');

        return redirect()->route('dashboard')->with('success', 'Tenant activo: '.$tenantModel->nombre);
    }

    private function availableTenantsQuery(User $user): Builder
    {
        if ($user->isPlatformAdmin()) {
            return Tenant::query()->where('estado', '!=', 'suspendido');
        }

        return $user->tenants()->where('estado', '!=', 'suspendido')->getQuery();
    }

    private function firstClientTenant(User $user): ?Tenant
    {
        return $user->tenants()
            ->where('estado', '!=', 'suspendido')
            ->orderBy('tenant_user.created_at')
            ->first();
    }
}
