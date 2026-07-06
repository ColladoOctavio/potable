<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveEmpresa
{
    public function handle(Request $request, Closure $next): Response
    {
        $empresaId = $request->session()->get('empresa_id');

        if ($empresaId && Empresa::whereKey($empresaId)->exists()) {
            return $next($request);
        }

        $request->session()->forget('empresa_id');

        if ($firstEmpresaId = Empresa::orderBy('nombre')->value('id')) {
            $request->session()->put('empresa_id', $firstEmpresaId);
        }

        return $next($request);
    }
}
