<?php

namespace App\Livewire\Forms;

use App\Models\Tenant\Cuenta;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\MovimientoCuenta;
use App\Models\Tenant\Venta;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MovimientoManager extends Component
{
    use WithPagination;

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
        $data = $this->validate($this->rules())['form'];
        $data['empresa_id'] = $this->activeEmpresaId();
        $data['cuenta_id'] = Cuenta::where('empresa_id', $this->activeEmpresaId())->value('id');
        $data['origen_type'] = $data['tipo'] === 'ingreso' && $data['origen_id'] ? Venta::class : ($data['tipo'] === 'egreso' && $data['origen_id'] ? Gasto::class : null);
        $data['origen_id'] = $data['origen_id'] ?: null;

        MovimientoCuenta::create($data);
        $this->form['concepto'] = '';
        $this->form['importe'] = 0;
        $this->form['origen_id'] = '';
        session()->flash('success', 'Movimiento registrado correctamente.');
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
            'ventas' => Venta::where('empresa_id', $empresaId)->latest('fecha')->get(),
            'gastos' => Gasto::where('empresa_id', $empresaId)->latest('fecha')->get(),
            'movimientos' => MovimientoCuenta::with(['empresa', 'cuenta'])->where('empresa_id', $empresaId)->latest('fecha')->paginate(12),
            'saldoActual' => $cuenta?->saldoActual() ?? 0,
        ]);
    }

    private function rules(): array
    {
        return [
            'form.empresa_id' => ['required', Rule::exists('empresas', 'id')->where('id', $this->activeEmpresaId())],
            'form.cuenta_id' => ['required', Rule::exists('cuentas', 'id')->where('empresa_id', $this->activeEmpresaId())],
            'form.fecha' => ['required', 'date'],
            'form.tipo' => ['required', 'in:ingreso,egreso,ajuste'],
            'form.concepto' => ['required', 'string', 'max:255'],
            'form.importe' => ['required', 'numeric', 'min:0'],
            'form.origen_id' => ['nullable', 'integer'],
            'form.observacion' => ['nullable', 'string'],
        ];
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
