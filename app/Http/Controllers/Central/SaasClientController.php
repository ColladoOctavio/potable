<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\Central\SaasClientCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SaasClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensurePlatformAdmin($request);

        $tenants = Tenant::with('owner')
            ->withCount('users')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('central.saas-clients.index', compact('tenants'));
    }

    public function create(Request $request): View
    {
        $this->ensurePlatformAdmin($request);

        return view('central.saas-clients.create');
    }

    public function store(Request $request, SaasClientCreator $creator): RedirectResponse
    {
        $this->ensurePlatformAdmin($request);

        $data = $this->validatedData($request);
        $tenant = $creator->create($data);

        return redirect()
            ->route('admin.clientes-saas.index')
            ->with('success', 'Cliente creado: '.$tenant->nombre.'. El usuario ya puede ingresar con su email y clave.');
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->ensurePlatformAdmin($request);

        $tenant->forceFill(['estado' => 'suspendido'])->save();

        if ($request->session()->get('tenant_id') === $tenant->getKey()) {
            $request->session()->forget(['tenant_id', 'empresa_id']);
        }

        return back()->with('success', 'Cliente inhabilitado: '.$tenant->nombre.'. Sus usuarios ya no pueden acceder.');
    }

    public function activate(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->ensurePlatformAdmin($request);

        $tenant->forceFill(['estado' => 'activo'])->save();

        return back()->with('success', 'Cliente habilitado: '.$tenant->nombre.'. Sus usuarios ya pueden acceder.');
    }

    private function ensurePlatformAdmin(Request $request): void
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $request->merge($this->normalizedInput($request->all()));

        $slug = Str::slug($request->input('tenant_slug') ?: $request->input('tenant_nombre'));
        $request->merge(['tenant_slug' => $slug]);

        return Validator::make($request->all(), [
            'tenant_nombre' => ['required', 'string', 'max:255'],
            'tenant_slug' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'id'),
                Rule::unique('tenants', 'slug'),
            ],
            'estado' => ['required', Rule::in(['activo', 'prueba'])],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
            'empresa_nombre' => ['required', 'string', 'max:255'],
            'empresa_cuit' => ['nullable', 'string', 'max:255'],
            'empresa_email' => ['nullable', 'email', 'max:255'],
            'empresa_telefono' => ['nullable', 'string', 'max:255'],
        ], [
            'tenant_slug.regex' => 'El identificador solo puede usar letras minusculas, numeros y guiones.',
            'owner_password.confirmed' => 'La confirmacion de la clave no coincide.',
        ], [
            'tenant_nombre' => 'nombre del tenant',
            'tenant_slug' => 'identificador del tenant',
            'estado' => 'estado',
            'owner_name' => 'nombre del usuario',
            'owner_email' => 'email del usuario',
            'owner_password' => 'clave',
            'empresa_nombre' => 'empresa inicial',
            'empresa_cuit' => 'CUIT',
            'empresa_email' => 'email de la empresa',
            'empresa_telefono' => 'telefono de la empresa',
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizedInput(array $input): array
    {
        return collect($input)
            ->map(function (mixed $value): mixed {
                if (! is_string($value)) {
                    return $value;
                }

                $value = trim($value);

                return $value === '' ? null : $value;
            })
            ->all();
    }
}
