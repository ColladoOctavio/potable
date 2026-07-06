<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CategoriaGasto;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrudController extends Controller
{
    private array $resources = [
        'empresas' => [
            'model' => Empresa::class,
            'title' => 'Empresas',
            'singular' => 'Empresa',
            'route' => 'empresas',
            'fields' => [
                'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                'cuit' => ['label' => 'CUIT', 'type' => 'text'],
                'email' => ['label' => 'Email', 'type' => 'email'],
                'telefono' => ['label' => 'Telefono', 'type' => 'text'],
                'direccion' => ['label' => 'Direccion', 'type' => 'text'],
                'observacion' => ['label' => 'Observacion', 'type' => 'textarea'],
            ],
            'columns' => ['nombre', 'cuit', 'email', 'telefono'],
        ],
        'lotes' => [
            'model' => Lote::class,
            'title' => 'Lotes / Campos',
            'singular' => 'Lote',
            'route' => 'lotes',
            'fields' => [
                'empresa_id' => ['label' => 'Empresa', 'type' => 'empresa', 'required' => true],
                'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                'ubicacion' => ['label' => 'Ubicacion', 'type' => 'text'],
                'hectareas' => ['label' => 'Hectareas', 'type' => 'number', 'step' => '0.01', 'required' => true],
                'observacion' => ['label' => 'Observacion', 'type' => 'textarea'],
            ],
            'columns' => ['empresa.nombre', 'nombre', 'ubicacion', 'hectareas'],
            'with' => ['empresa'],
        ],
        'clientes' => [
            'model' => Cliente::class,
            'title' => 'Clientes',
            'singular' => 'Cliente',
            'route' => 'clientes',
            'fields' => [
                'empresa_id' => ['label' => 'Empresa', 'type' => 'empresa', 'required' => true],
                'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                'cuit' => ['label' => 'CUIT', 'type' => 'text'],
                'telefono' => ['label' => 'Telefono', 'type' => 'text'],
                'email' => ['label' => 'Email', 'type' => 'email'],
                'direccion' => ['label' => 'Direccion', 'type' => 'text'],
                'saldo_inicial' => ['label' => 'Saldo inicial', 'type' => 'number', 'step' => '0.01'],
                'observacion' => ['label' => 'Observacion', 'type' => 'textarea'],
            ],
            'columns' => ['empresa.nombre', 'nombre', 'cuit', 'saldo_inicial'],
            'with' => ['empresa'],
        ],
        'proveedores' => [
            'model' => Proveedor::class,
            'title' => 'Proveedores',
            'singular' => 'Proveedor',
            'route' => 'proveedores',
            'fields' => [
                'empresa_id' => ['label' => 'Empresa', 'type' => 'empresa', 'required' => true],
                'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                'cuit' => ['label' => 'CUIT', 'type' => 'text'],
                'telefono' => ['label' => 'Telefono', 'type' => 'text'],
                'email' => ['label' => 'Email', 'type' => 'email'],
                'direccion' => ['label' => 'Direccion', 'type' => 'text'],
                'saldo_inicial' => ['label' => 'Saldo inicial', 'type' => 'number', 'step' => '0.01'],
                'observacion' => ['label' => 'Observacion', 'type' => 'textarea'],
            ],
            'columns' => ['empresa.nombre', 'nombre', 'cuit', 'saldo_inicial'],
            'with' => ['empresa'],
        ],
        'categorias-gastos' => [
            'model' => CategoriaGasto::class,
            'title' => 'Categorias de gasto',
            'singular' => 'Categoria',
            'route' => 'categorias-gastos',
            'fields' => [
                'empresa_id' => ['label' => 'Empresa', 'type' => 'empresa', 'required' => true],
                'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                'descripcion' => ['label' => 'Descripcion', 'type' => 'textarea'],
            ],
            'columns' => ['empresa.nombre', 'nombre', 'descripcion'],
            'with' => ['empresa'],
        ],
    ];

    public function index(Request $request): View
    {
        $config = $this->config($request);
        $empresaId = $this->activeEmpresaId();
        $items = $config['model']::query()
            ->when($config['with'] ?? null, fn ($query, $with) => $query->with($with))
            ->when($this->usesEmpresa($config), fn ($query) => $query->where('empresa_id', $empresaId))
            ->latest()
            ->paginate(10);

        return view('tenant.crud.index', [
            'config' => $config,
            'activeEmpresa' => $empresaId ? Empresa::find($empresaId) : null,
            'items' => $items,
            'empresas' => Empresa::orderBy('nombre')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('tenant.crud.form', [
            'config' => $this->config($request),
            'activeEmpresa' => $this->activeEmpresaId() ? Empresa::find($this->activeEmpresaId()) : null,
            'item' => null,
            'empresas' => Empresa::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $config = $this->config($request);
        $data = $request->validate($this->rules($config));

        if ($this->usesEmpresa($config)) {
            $data['empresa_id'] = $this->activeEmpresaId();
        }

        $config['model']::create($data);

        return redirect()->route($config['route'].'.index')->with('success', $config['singular'].' creado correctamente.');
    }

    public function edit(Request $request, int $id): View
    {
        $config = $this->config($request);
        $empresaId = $this->activeEmpresaId();
        $item = $config['model']::query()
            ->when($this->usesEmpresa($config), fn ($query) => $query->where('empresa_id', $empresaId))
            ->findOrFail($id);

        return view('tenant.crud.form', [
            'config' => $config,
            'activeEmpresa' => $empresaId ? Empresa::find($empresaId) : null,
            'item' => $item,
            'empresas' => Empresa::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $config = $this->config($request);
        $data = $request->validate($this->rules($config));

        if ($this->usesEmpresa($config)) {
            $data['empresa_id'] = $this->activeEmpresaId();
        }

        $item = $config['model']::query()
            ->when($this->usesEmpresa($config), fn ($query) => $query->where('empresa_id', $this->activeEmpresaId()))
            ->findOrFail($id);
        $item->update($data);

        return redirect()->route($config['route'].'.index')->with('success', $config['singular'].' actualizado correctamente.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $config = $this->config($request);
        $config['model']::query()
            ->when($this->usesEmpresa($config), fn ($query) => $query->where('empresa_id', $this->activeEmpresaId()))
            ->findOrFail($id)
            ->delete();

        return redirect()->route($config['route'].'.index')->with('success', $config['singular'].' eliminado.');
    }

    private function config(Request $request): array
    {
        return $this->resources[$request->route('resource')] ?? abort(404);
    }

    private function rules(array $config): array
    {
        $rules = [];

        foreach ($config['fields'] as $name => $field) {
            $fieldRules = [];
            $fieldRules[] = ($field['required'] ?? false) ? 'required' : 'nullable';

            $fieldRules[] = match ($field['type']) {
                'email' => 'email',
                'number' => 'numeric',
                'empresa' => Rule::exists('empresas', 'id'),
                default => 'string',
            };

            if ($name === 'hectareas') {
                $fieldRules[] = 'gt:0';
            }

            if (in_array($name, ['saldo_inicial'], true)) {
                $fieldRules[] = 'min:0';
            }

            $rules[$name] = $fieldRules;
        }

        return $rules;
    }

    private function usesEmpresa(array $config): bool
    {
        return array_key_exists('empresa_id', $config['fields']);
    }

    private function activeEmpresaId(): ?int
    {
        return session('empresa_id') ? (int) session('empresa_id') : null;
    }
}
