<?php

namespace App\Livewire\Reportes;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Lote;
use App\Models\Tenant\Venta;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteVentas extends Component
{
    use WithPagination;

    public array $filtros = ['cliente_id' => '', 'lote_id' => '', 'desde' => '', 'hasta' => ''];

    public function render()
    {
        $empresaId = $this->activeEmpresaId();
        $query = Venta::with(['empresa', 'cliente', 'lote'])
            ->where('empresa_id', $empresaId)
            ->when($this->filtros['cliente_id'], fn ($q, $v) => $q->where('cliente_id', $v))
            ->when($this->filtros['lote_id'], fn ($q, $v) => $q->where('lote_id', $v))
            ->when($this->filtros['desde'], fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
            ->when($this->filtros['hasta'], fn ($q, $v) => $q->whereDate('fecha', '<=', $v));

        $summary = (clone $query)->selectRaw('sum(importe_total) total, sum(bolsas) bolsas')->first();
        $total = (float) $summary->total;
        $bolsas = (float) $summary->bolsas;

        return view('livewire.reportes.reporte-ventas', [
            'clientes' => Cliente::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'lotes' => Lote::where('empresa_id', $empresaId)->orderBy('nombre')->get(),
            'ventas' => $query->latest('fecha')->paginate(12),
            'summary' => [
                'total' => $total,
                'bolsas' => $bolsas,
                'promedio' => $bolsas > 0 ? $total / $bolsas : 0,
            ],
        ]);
    }

    private function activeEmpresaId(): int
    {
        return (int) session('empresa_id');
    }
}
