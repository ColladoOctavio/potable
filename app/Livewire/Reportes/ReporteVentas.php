<?php

namespace App\Livewire\Reportes;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Venta;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteVentas extends Component
{
    use WithPagination;

    public array $filtros = ['cliente_id' => '', 'lote_id' => '', 'desde' => '', 'hasta' => ''];

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
        $query = Venta::with(['empresa', 'cliente', 'lote'])
            ->where('ventas.empresa_id', $empresaId)
            ->when($this->filtros['cliente_id'], fn ($q, $v) => $q->where('ventas.cliente_id', $v))
            ->when($this->filtros['lote_id'], fn ($q, $v) => $q->where('ventas.lote_id', $v))
            ->when($this->filtros['desde'], fn ($q, $v) => $q->whereDate('ventas.fecha', '>=', $v))
            ->when($this->filtros['hasta'], fn ($q, $v) => $q->whereDate('ventas.fecha', '<=', $v));

        $summary = (clone $query)->selectRaw('sum(importe_total) total, sum(bolsas) bolsas')->first();
        $total = (float) $summary->total;
        $bolsas = (float) $summary->bolsas;

        return view('livewire.reportes.reporte-ventas', [
            'clientes' => Cliente::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'lotes' => Lote::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'ventas' => $this->applySorting((clone $query)->select('ventas.*'))->paginate(12),
            'summary' => [
                'total' => $total,
                'bolsas' => $bolsas,
                'promedio' => $bolsas > 0 ? $total / $bolsas : 0,
            ],
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
                ->join('empresas', 'empresas.id', '=', 'ventas.empresa_id')
                ->orderBy('empresas.nombre', $direction),
            'cliente' => $query
                ->leftJoin('clientes', 'clientes.id', '=', 'ventas.cliente_id')
                ->orderBy('clientes.nombre', $direction),
            'lote' => $query
                ->leftJoin('lotes', 'lotes.id', '=', 'ventas.lote_id')
                ->orderBy('lotes.nombre', $direction),
            default => $query->orderBy($this->sortableFields()[$this->sortField], $direction),
        };

        return $query->orderBy('ventas.id', $direction);
    }

    /**
     * @return array<string, string>
     */
    private function sortableFields(): array
    {
        return [
            'fecha' => 'ventas.fecha',
            'empresa' => 'empresas.nombre',
            'cliente' => 'clientes.nombre',
            'lote' => 'lotes.nombre',
            'bolsas' => 'ventas.bolsas',
            'total' => 'ventas.importe_total',
        ];
    }

    private function defaultDirectionFor(string $field): string
    {
        return in_array($field, ['fecha', 'bolsas', 'total'], true) ? 'desc' : 'asc';
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
