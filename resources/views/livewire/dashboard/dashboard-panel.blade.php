<div>
    @php($money = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.'))
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Panel operativo</h1>
            <div class="text-muted">Empresa activa: {{ $activeEmpresa?->nombre ?? 'Sin empresa activa' }}.</div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            'ventas' => 'Total ventas',
            'cobrado' => 'Total cobrado',
            'pendiente_cobro' => 'Pendiente de cobro',
            'gastos' => 'Total gastos',
            'pagado' => 'Total pagado',
            'pendiente_pago' => 'Pendiente de pago',
            'resultado' => 'Resultado estimado',
            'saldo' => 'Saldo de cuenta',
            'kilos' => 'Kilos vendidos',
            'costo_hectarea' => 'Costo por hectarea',
            'clientes_deuda' => 'Clientes con deuda',
            'proveedores_deuda' => 'Proveedores con deuda',
        ] as $key => $label)
            <div class="col-sm-6 col-xl-3">
                <div class="metric-card p-3 h-100">
                    <div class="metric-label">{{ $label }}</div>
                    <div class="metric-value">
                        @if(in_array($key, ['kilos', 'clientes_deuda', 'proveedores_deuda']))
                            {{ number_format($metrics[$key], 0, ',', '.') }}
                        @else
                            {{ $money($metrics[$key]) }}
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="panel p-3 h-100">
                <h2 class="h6">Gastos por categoria</h2>
                @forelse($gastosPorCategoria as $row)
                    <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $row->categoriaGasto?->nombre ?? 'Sin categoria' }}</span><strong>{{ $money($row->total) }}</strong></div>
                @empty <div class="empty-state">Sin gastos.</div> @endforelse
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel p-3 h-100">
                <h2 class="h6">Ventas por cliente</h2>
                @forelse($ventasPorCliente as $row)
                    <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $row->cliente?->nombre ?? 'Sin cliente' }}</span><strong>{{ $money($row->total) }}</strong></div>
                @empty <div class="empty-state">Sin ventas.</div> @endforelse
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel p-3 h-100">
                <h2 class="h6">Resultado por lote</h2>
                @forelse($resultadoPorLote as $row)
                    <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $row->lote?->nombre ?? 'Sin lote' }}</span><strong>{{ $money($row->resultado) }}</strong></div>
                @empty <div class="empty-state">Sin lotes.</div> @endforelse
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach([['Ultimas ventas', $ultimasVentas, 'cliente'], ['Ultimos gastos', $ultimosGastos, 'proveedor'], ['Ultimos movimientos', $ultimosMovimientos, 'tipo']] as [$title, $rows, $relation])
            <div class="col-xl-4">
                <div class="panel p-3 h-100">
                    <h2 class="h6">{{ $title }}</h2>
                    @forelse($rows as $row)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold">{{ $row->descripcion ?? $row->concepto }}</div>
                            <div class="small text-muted">{{ optional($row->fecha)->format('d/m/Y') }} · {{ data_get($row, $relation.'.nombre', data_get($row, $relation)) }}</div>
                        </div>
                    @empty
                        <div class="empty-state">No hay datos.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
