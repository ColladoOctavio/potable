<?php

namespace App\Livewire\Reportes;

use App\Models\Tenant\CategoriaGasto;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Proveedor;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteGastos extends Component
{
    use WithPagination;

    public array $filtros = ['proveedor_id' => '', 'categoria_gasto_id' => '', 'lote_id' => '', 'desde' => '', 'hasta' => ''];

    public string $sortField = 'fecha';

    public string $sortDirection = 'desc';

    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, $this->sortableFields())) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $this->defaultDirectionFor($field);
        }

        $this->resetPage();
    }

    public function updatedFiltros(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $query = Gasto::with(['empresa', 'proveedor', 'categoriaGasto', 'lote'])
            ->where('gastos.empresa_id', $empresaId)
            ->when($this->filtros['proveedor_id'], fn ($q, $v) => $q->where('gastos.proveedor_id', $v))
            ->when($this->filtros['categoria_gasto_id'], fn ($q, $v) => $q->where('gastos.categoria_gasto_id', $v))
            ->when($this->filtros['lote_id'], fn ($q, $v) => $q->where('gastos.lote_id', $v))
            ->when($this->filtros['desde'], fn ($q, $v) => $q->whereDate('gastos.fecha', '>=', $v))
            ->when($this->filtros['hasta'], fn ($q, $v) => $q->whereDate('gastos.fecha', '<=', $v));

        $total = (float) (clone $query)->sum('importe_total');

        return view('livewire.reportes.reporte-gastos', [
            'proveedores' => Proveedor::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'categorias' => CategoriaGasto::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'lotes' => Lote::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'gastos' => $this->applySorting((clone $query)->select('gastos.*'))->paginate(12),
            'summary' => ['total' => $total],
            'porCategoria' => Gasto::selectRaw('categoria_gasto_id, sum(importe_total) total')->where('empresa_id', $empresaId)->with('categoriaGasto')->groupBy('categoria_gasto_id')->orderByDesc('total')->get(),
        ]);
    }

    private function applySorting(Builder $query): Builder
    {
        if (! array_key_exists($this->sortField, $this->sortableFields())) {
            $this->sortField = 'fecha';
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match ($this->sortField) {
            'empresa' => $query
                ->join('empresas', 'empresas.id', '=', 'gastos.empresa_id')
                ->orderBy('empresas.nombre', $direction),
            'proveedor' => $query
                ->leftJoin('proveedores', 'proveedores.id', '=', 'gastos.proveedor_id')
                ->orderBy('proveedores.nombre', $direction),
            'categoria' => $query
                ->leftJoin('categorias_gasto', 'categorias_gasto.id', '=', 'gastos.categoria_gasto_id')
                ->orderBy('categorias_gasto.nombre', $direction),
            'lote' => $query
                ->leftJoin('lotes', 'lotes.id', '=', 'gastos.lote_id')
                ->orderBy('lotes.nombre', $direction),
            default => $query->orderBy($this->sortableFields()[$this->sortField], $direction),
        };

        return $query->orderBy('gastos.id', $direction);
    }

    /**
     * @return array<string, string>
     */
    private function sortableFields(): array
    {
        return [
            'fecha' => 'gastos.fecha',
            'empresa' => 'empresas.nombre',
            'proveedor' => 'proveedores.nombre',
            'categoria' => 'categorias_gasto.nombre',
            'lote' => 'lotes.nombre',
            'total' => 'gastos.importe_total',
        ];
    }

    private function defaultDirectionFor(string $field): string
    {
        return in_array($field, ['fecha', 'total'], true) ? 'desc' : 'asc';
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
