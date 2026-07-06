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

        if ($this->editingId) {
            Venta::where('empresa_id', $this->activeEmpresaId())->findOrFail($this->editingId)->update($data);
        } else {
            Venta::create($data);
        }
        $this->resetForm();
        session()->flash('success', 'Venta guardada correctamente.');
    }

    public function edit(int $id): void
    {
        $venta = Venta::where('empresa_id', $this->activeEmpresaId())->findOrFail($id);
        $this->editingId = $venta->id;
        $this->form = $venta->only(array_keys($this->form));
        $this->form['fecha'] = $venta->fecha->toDateString();
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

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
