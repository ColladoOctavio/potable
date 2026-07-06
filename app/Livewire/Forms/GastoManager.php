<?php

namespace App\Livewire\Forms;

use App\Models\Tenant\CategoriaGasto;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Proveedor;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GastoManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public array $form = [
        'empresa_id' => '',
        'proveedor_id' => '',
        'lote_id' => '',
        'categoria_gasto_id' => '',
        'fecha' => '',
        'descripcion' => '',
        'importe_total' => 0,
        'estado_pago' => 'pendiente',
        'observacion' => '',
    ];

    public function mount(): void
    {
        $this->form['fecha'] = now()->toDateString();
        $this->form['empresa_id'] = $this->activeEmpresaId();
    }

    public function save(): void
    {
        $data = $this->validate($this->rules())['form'];
        $data['empresa_id'] = $this->activeEmpresaId();
        $data['proveedor_id'] = $data['proveedor_id'] ?: null;
        $data['lote_id'] = $data['lote_id'] ?: null;

        if ($this->editingId) {
            Gasto::where('empresa_id', $this->activeEmpresaId())->findOrFail($this->editingId)->update($data);
        } else {
            Gasto::create($data);
        }
        $this->resetForm();
        session()->flash('success', 'Gasto guardado correctamente.');
    }

    public function edit(int $id): void
    {
        $gasto = Gasto::where('empresa_id', $this->activeEmpresaId())->findOrFail($id);
        $this->editingId = $gasto->id;
        $this->form = $gasto->only(array_keys($this->form));
        $this->form['fecha'] = $gasto->fecha->toDateString();
    }

    public function delete(int $id): void
    {
        Gasto::where('empresa_id', $this->activeEmpresaId())->findOrFail($id)->delete();
    }

    public function resetForm(): void
    {
        $empresa = $this->activeEmpresaId();
        $this->editingId = null;
        $this->form = ['empresa_id' => $empresa, 'proveedor_id' => '', 'lote_id' => '', 'categoria_gasto_id' => '', 'fecha' => now()->toDateString(), 'descripcion' => '', 'importe_total' => 0, 'estado_pago' => 'pendiente', 'observacion' => ''];
    }

    public function render()
    {
        $empresaId = $this->activeEmpresaId();

        return view('livewire.forms.gasto-manager', [
            'activeEmpresa' => Empresa::find($empresaId),
            'proveedores' => Proveedor::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'lotes' => Lote::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'categorias' => CategoriaGasto::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'gastos' => Gasto::with(['empresa', 'proveedor', 'categoriaGasto', 'lote'])->where('empresa_id', $empresaId)->latest('fecha')->paginate(10),
        ]);
    }

    private function rules(): array
    {
        return [
            'form.empresa_id' => ['required', Rule::exists('empresas', 'id')->where('id', $this->activeEmpresaId())],
            'form.proveedor_id' => ['nullable', Rule::exists('proveedores', 'id')->where('empresa_id', $this->activeEmpresaId())],
            'form.lote_id' => ['nullable', Rule::exists('lotes', 'id')->where('empresa_id', $this->activeEmpresaId())],
            'form.categoria_gasto_id' => ['required', Rule::exists('categorias_gasto', 'id')->where('empresa_id', $this->activeEmpresaId())],
            'form.fecha' => ['required', 'date'],
            'form.descripcion' => ['required', 'string', 'max:255'],
            'form.importe_total' => ['required', 'numeric', 'min:0'],
            'form.estado_pago' => ['required', 'in:pendiente,parcial,pagado'],
            'form.observacion' => ['nullable', 'string'],
        ];
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
