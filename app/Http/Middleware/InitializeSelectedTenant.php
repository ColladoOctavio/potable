<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeSelectedTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->session()->get('tenant_id');

        $user = $request->user();
        $tenantIsAllowed = $user?->isPlatformAdmin()
            ? Tenant::whereKey($tenantId)->where('estado', '!=', 'suspendido')->exists()
            : $user?->tenants()
                ->where('estado', '!=', 'suspendido')
                ->orderBy('tenant_user.created_at')
                ->value('tenants.id') === $tenantId;

        if (! $tenantId || ! $tenantIsAllowed) {
            return redirect()->route('tenants.index')->withErrors('Seleccioná un tenant para continuar.');
        }

        $tenant = Tenant::findOrFail($tenantId);
        tenancy()->initialize($tenant);

        $response = $next($request);

        tenancy()->end();

        return $response;
    }
}
