<?php

namespace App\Livewire\Forms;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Venta;
use App\Services\Tenant\Ventas\VentaCreator;
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
        'bolsas' => 0,
        'precio_por_bolsa' => 0,
        'peso_bolsa_kg' => 20,
        'observacion' => '',
    ];

    public function mount(): void
    {
        $this->form['fecha'] = now()->toDateString();
        $this->form['empresa_id'] = $this->activeEmpresaId();
        $this->form['peso_bolsa_kg'] = $this->activeEmpresaPesoBolsa();
    }

    public function total(): float
    {
        return (float) $this->form['bolsas'] * (float) $this->form['precio_por_bolsa'];
    }

    public function save(): void
    {
        $this->normalizeForm();
        $data = $this->validate($this->rules())['form'];
        $data['empresa_id'] = $this->activeEmpresaId();
        $data['cliente_id'] = $data['cliente_id'] ?: null;
        $data['lote_id'] = $data['lote_id'] ?: null;
        $data['peso_bolsa_kg'] = $data['peso_bolsa_kg'] ?: $this->activeEmpresaPesoBolsa();
        $data['importe_total'] = (float) $data['bolsas'] * (float) $data['precio_por_bolsa'];

        if ($this->editingId) {
            Venta::where('empresa_id', $this->activeEmpresaId())->findOrFail($this->editingId)->update($data);
        } else {
            app(VentaCreator::class)->create($data);
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
        $this->form = ['empresa_id' => $empresa, 'cliente_id' => '', 'lote_id' => '', 'fecha' => now()->toDateString(), 'descripcion' => '', 'bolsas' => 0, 'precio_por_bolsa' => 0, 'peso_bolsa_kg' => $this->activeEmpresaPesoBolsa(), 'observacion' => ''];
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
            'form.bolsas' => ['required', 'numeric', 'min:0'],
            'form.precio_por_bolsa' => ['required', 'numeric', 'min:0'],
            'form.peso_bolsa_kg' => ['required', 'numeric', 'gt:0'],
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

    protected function messages(): array
    {
        return [
            'form.fecha.required' => 'Ingresá una fecha.',
            'form.fecha.date' => 'Ingresá una fecha válida.',
            'form.descripcion.required' => 'Ingresá una descripción.',
            'form.descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
            'form.bolsas.required' => 'Ingresá las bolsas.',
            'form.bolsas.numeric' => 'Ingresá una cantidad de bolsas válida.',
            'form.bolsas.min' => 'Las bolsas no pueden ser negativas.',
            'form.precio_por_bolsa.required' => 'Ingresá el precio por bolsa.',
            'form.precio_por_bolsa.numeric' => 'Ingresá un precio por bolsa válido.',
            'form.precio_por_bolsa.min' => 'El precio por bolsa no puede ser negativo.',
            'form.peso_bolsa_kg.required' => 'Ingresá el peso de la bolsa.',
            'form.peso_bolsa_kg.numeric' => 'Ingresá un peso de bolsa válido.',
            'form.peso_bolsa_kg.gt' => 'El peso de la bolsa debe ser mayor a 0.',
        ];
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }

    private function activeEmpresaPesoBolsa(): float
    {
        return (float) (Empresa::whereKey($this->activeEmpresaId())->value('peso_bolsa_kg') ?? 20);
    }
}
