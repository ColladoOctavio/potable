<?php

namespace App\Livewire\Forms;

use App\Models\Tenant\Cuenta;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\MovimientoCuenta;
use App\Models\Tenant\Proveedor;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MovimientoManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public array $form = [
        'empresa_id' => '',
        'cuenta_id' => '',
        'fecha' => '',
        'tipo' => 'ingreso',
        'concepto' => '',
        'importe' => 0,
        'origen_id' => '',
        'observacion' => '',
    ];

    public function mount(): void
    {
        $empresa = $this->activeEmpresaId();
        $this->form['empresa_id'] = $empresa;
        $this->form['cuenta_id'] = Cuenta::where('empresa_id', $empresa)->value('id') ?: '';
        $this->form['fecha'] = now()->toDateString();
    }

    public function save(): void
    {
        $this->normalizeForm();
        $data = $this->validate($this->rules())['form'];
        $data['empresa_id'] = $this->activeEmpresaId();
        $data['cuenta_id'] = Cuenta::where('empresa_id', $this->activeEmpresaId())->value('id');
        $data['origen_type'] = $data['tipo'] === 'ingreso'
            ? Cliente::class
            : ($data['tipo'] === 'egreso' ? Proveedor::class : null);
        $data['origen_id'] = $data['origen_id'] ?: null;

        MovimientoCuenta::create($data);
        $this->form['concepto'] = '';
        $this->form['importe'] = 0;
        $this->form['origen_id'] = '';
        $this->showForm = false;
        $this->resetValidation();
        session()->flash('success', 'Movimiento registrado correctamente.');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function updatedFormTipo(): void
    {
        $this->form['origen_id'] = '';
    }

    public function delete(int $id): void
    {
        MovimientoCuenta::where('empresa_id', $this->activeEmpresaId())->findOrFail($id)->delete();
    }

    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $cuenta = Cuenta::where('empresa_id', $empresaId)->first();

        return view('livewire.forms.movimiento-manager', [
            'activeEmpresa' => Empresa::find($empresaId),
            'cuenta' => $cuenta,
            'clientes' => Cliente::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'proveedores' => Proveedor::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'movimientos' => MovimientoCuenta::with(['empresa', 'cuenta', 'origen'])->where('empresa_id', $empresaId)->latest('fecha')->paginate(12),
            'saldoActual' => $cuenta?->saldoActual() ?? 0,
        ]);
    }

    private function rules(): array
    {
        $origenRules = $this->form['tipo'] === 'ajuste' ? ['nullable'] : ['required', 'integer'];

        if ($this->form['tipo'] === 'ingreso') {
            $origenRules[] = Rule::exists('clientes', 'id')->where('empresa_id', $this->activeEmpresaId());
        }

        if ($this->form['tipo'] === 'egreso') {
            $origenRules[] = Rule::exists('proveedores', 'id')->where('empresa_id', $this->activeEmpresaId());
        }

        return [
            'form.empresa_id' => ['required', Rule::exists('empresas', 'id')->where('id', $this->activeEmpresaId())],
            'form.cuenta_id' => ['required', Rule::exists('cuentas', 'id')->where('empresa_id', $this->activeEmpresaId())],
            'form.fecha' => ['required', 'date'],
            'form.tipo' => ['required', 'in:ingreso,egreso,ajuste'],
            'form.concepto' => ['required', 'string', 'max:255'],
            'form.importe' => ['required', 'numeric', 'min:0'],
            'form.origen_id' => $origenRules,
            'form.observacion' => ['nullable', 'string'],
        ];
    }

    private function normalizeForm(): void
    {
        foreach ($this->form as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $this->form[$key] = $value === '' ? null : $value;
            }
        }
    }

    private function resetForm(): void
    {
        $empresa = $this->activeEmpresaId();
        $this->form = [
            'empresa_id' => $empresa,
            'cuenta_id' => Cuenta::where('empresa_id', $empresa)->value('id') ?: '',
            'fecha' => now()->toDateString(),
            'tipo' => 'ingreso',
            'concepto' => '',
            'importe' => 0,
            'origen_id' => '',
            'observacion' => '',
        ];
        $this->resetValidation();
    }

    protected function messages(): array
    {
        return [
            'form.fecha.required' => 'Ingresá una fecha.',
            'form.fecha.date' => 'Ingresá una fecha válida.',
            'form.tipo.required' => 'Seleccioná un tipo de movimiento.',
            'form.tipo.in' => 'Seleccioná un tipo de movimiento válido.',
            'form.concepto.required' => 'Ingresá un concepto.',
            'form.concepto.max' => 'El concepto no puede superar los 255 caracteres.',
            'form.importe.required' => 'Ingresá un importe.',
            'form.importe.numeric' => 'Ingresá un importe numérico.',
            'form.importe.min' => 'El importe no puede ser negativo.',
            'form.origen_id.required' => 'Seleccioná un origen.',
            'form.origen_id.exists' => 'Seleccioná un origen válido.',
        ];
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
