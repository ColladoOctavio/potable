<?php

namespace App\Livewire\Reportes;

use App\Models\Tenant\CategoriaGasto;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Proveedor;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteGastos extends Component
{
    use WithPagination;

    public array $filtros = ['proveedor_id' => '', 'categoria_gasto_id' => '', 'lote_id' => '', 'estado_pago' => '', 'desde' => '', 'hasta' => ''];

    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $query = Gasto::with(['empresa', 'proveedor', 'categoriaGasto', 'lote'])
            ->where('empresa_id', $empresaId)
            ->when($this->filtros['proveedor_id'], fn ($q, $v) => $q->where('proveedor_id', $v))
            ->when($this->filtros['categoria_gasto_id'], fn ($q, $v) => $q->where('categoria_gasto_id', $v))
            ->when($this->filtros['lote_id'], fn ($q, $v) => $q->where('lote_id', $v))
            ->when($this->filtros['estado_pago'], fn ($q, $v) => $q->where('estado_pago', $v))
            ->when($this->filtros['desde'], fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
            ->when($this->filtros['hasta'], fn ($q, $v) => $q->whereDate('fecha', '<=', $v));

        $total = (float) (clone $query)->sum('importe_total');
        $pagado = (float) (clone $query)->where('estado_pago', 'pagado')->sum('importe_total');

        return view('livewire.reportes.reporte-gastos', [
            'proveedores' => Proveedor::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'categorias' => CategoriaGasto::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'lotes' => Lote::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'gastos' => $query->latest('fecha')->paginate(12),
            'summary' => ['total' => $total, 'pagado' => $pagado, 'pendiente' => max($total - $pagado, 0)],
            'porCategoria' => Gasto::selectRaw('categoria_gasto_id, sum(importe_total) total')->where('empresa_id', $empresaId)->with('categoriaGasto')->groupBy('categoria_gasto_id')->orderByDesc('total')->get(),
        ]);
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
