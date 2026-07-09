<?php

namespace App\Livewire\Forms;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Venta;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class VentaManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public bool $showForm = false;

    public array $form = [
        'empresa_id' => '',
        'cliente_id' => '',
        'lote_id' => '',
        'fecha' => '',
        'descripcion' => '',
        'kilos' => 0,
        'precio_por_kg' => 0,
        'estado_cobro' => 'pendiente',
        'observacion' => '',
    ];

    public function mount(): void
    {
        $this->form['fecha'] = now()->toDateString();
        $this->form['empresa_id'] = $this->activeEmpresaId();
    }

    public function total(): float
    {
        return (float) $this->form['kilos'] * (float) $this->form['precio_por_kg'];
    }

    public function save(): void
    {
        $data = $this->validate($this->rules())['form'];
        $data['empresa_id'] = $this->activeEmpresaId();
        $data['cliente_id'] = $data['cliente_id'] ?: null;
        $data['lote_id'] = $data['lote_id'] ?: null;
        $data['importe_total'] = $this->total();
        $data['estado_cobro'] = 'pendiente';

        if ($this->editingId) {
            Venta::where('empresa_id', $this->activeEmpresaId())->findOrFail($this->editingId)->update($data);
        } else {
            Venta::create($data);
        }

        $this->closeForm();
        session()->flash('success', 'Venta guardada correctamente.');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $venta = Venta::where('empresa_id', $this->activeEmpresaId())->findOrFail($id);
        $this->editingId = $venta->id;
        $this->form = $venta->only(array_keys($this->form));
        $this->form['fecha'] = $venta->fecha->toDateString();
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        Venta::where('empresa_id', $this->activeEmpresaId())->findOrFail($id)->delete();
    }

    public function resetForm(): void
    {
        $empresa = $this->activeEmpresaId();
        $this->editingId = null;
        $this->form = ['empresa_id' => $empresa, 'cliente_id' => '', 'lote_id' => '', 'fecha' => now()->toDateString(), 'descripcion' => '', 'kilos' => 0, 'precio_por_kg' => 0, 'estado_cobro' => 'pendiente', 'observacion' => ''];
        $this->resetValidation();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render()
    {
        $empresaId = $this->activeEmpresaId();

        return view('livewire.forms.venta-manager', [
            'activeEmpresa' => Empresa::find($empresaId),
            'clientes' => Cliente::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'lotes' => Lote::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'ventas' => Venta::with(['empresa', 'cliente', 'lote'])->where('empresa_id', $empresaId)->latest('fecha')->paginate(10),
        ]);
    }

    private function rules(): array
    {
        return [
            'form.empresa_id' => ['required', Rule::exists('empresas', 'id')->where('id', $this->activeEmpresaId())],
            'form.cliente_id' => ['nullable', Rule::exists('clientes', 'id')->where('empresa_id', $this->activeEmpresaId())],
            'form.lote_id' => ['nullable', Rule::exists('lotes', 'id')->where('empresa_id', $this->activeEmpresaId())],
            'form.fecha' => ['required', 'date'],
            'form.descripcion' => ['required', 'string', 'max:255'],
            'form.kilos' => ['required', 'numeric', 'min:0'],
            'form.precio_por_kg' => ['required', 'numeric', 'min:0'],
            'form.estado_cobro' => ['required', 'in:pendiente,parcial,cobrada'],
            'form.observacion' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.fecha.required' => 'Ingresá una fecha.',
            'form.fecha.date' => 'Ingresá una fecha válida.',
            'form.descripcion.required' => 'Ingresá una descripción.',
            'form.descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
            'form.kilos.required' => 'Ingresá los kilos.',
            'form.kilos.numeric' => 'Ingresá una cantidad de kilos válida.',
            'form.kilos.min' => 'Los kilos no pueden ser negativos.',
            'form.precio_por_kg.required' => 'Ingresá el precio por kg.',
            'form.precio_por_kg.numeric' => 'Ingresá un precio por kg válido.',
            'form.precio_por_kg.min' => 'El precio por kg no puede ser negativo.',
            'form.estado_cobro.required' => 'Seleccioná un estado.',
            'form.estado_cobro.in' => 'Seleccioná un estado válido.',
        ];
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
