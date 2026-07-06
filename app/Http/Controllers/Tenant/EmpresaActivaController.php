<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmpresaActivaController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id'],
        ]);

        $empresa = Empresa::findOrFail($data['empresa_id']);
        $request->session()->put('empresa_id', $empresa->id);

        return back()->with('success', 'Empresa activa: '.$empresa->nombre);
    }
}
