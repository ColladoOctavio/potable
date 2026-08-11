<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Cuenta;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\Lote;
use App\Models\Tenant\MovimientoCuenta;
use App\Models\Tenant\Proveedor;
use App\Models\Tenant\Venta;
use Illuminate\Console\Command;

class DiversifyDemoData extends Command
{
    protected $signature = 'potable:demo-diversify {--tenant=* : Tenant especifico a recalcular}';

    protected $description = 'Recalcula los datos demo para que cada empresa tenga metricas distintas.';

    public function handle(): int
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($query, $tenants) => $query->whereIn('id', $tenants))
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant) {
                $this->diversifyTenant();
                $this->info("Datos demo diversificados para {$tenant->id}.");
            });
        }

        return self::SUCCESS;
    }

    private function diversifyTenant(): void
    {
        $profiles = [
            ['escala_ventas' => 1.00, 'escala_gastos' => 1.00, 'escala_precio' => 1.00, 'saldo_cuenta' => 120000, 'saldo_cliente' => 1.00, 'saldo_proveedor' => 1.00],
            ['escala_ventas' => 0.68, 'escala_gastos' => 0.74, 'escala_precio' => 0.96, 'saldo_cuenta' => 85000, 'saldo_cliente' => 0.65, 'saldo_proveedor' => 0.80],
            ['escala_ventas' => 1.34, 'escala_gastos' => 1.18, 'escala_precio' => 1.08, 'saldo_cuenta' => 175000, 'saldo_cliente' => 1.35, 'saldo_proveedor' => 1.20],
        ];

        Empresa::orderBy('id')->get()->each(function (Empresa $empresa, int $index) use ($profiles) {
            $profile = $profiles[$index] ?? $profiles[0];

            Cuenta::where('empresa_id', $empresa->id)->update(['saldo_inicial' => $profile['saldo_cuenta']]);

            Lote::where('empresa_id', $empresa->id)->orderBy('id')->get()->each(function (Lote $lote, int $loteIndex) use ($profile) {
                $base = $loteIndex === 0 ? 28 : 34;
                $lote->update(['hectareas' => round($base * $profile['escala_ventas'], 2)]);
            });

            Cliente::where('empresa_id', $empresa->id)->where('nombre', 'Mercado Central Patagonia')->update(['saldo_inicial' => round(45000 * $profile['saldo_cliente'], 2)]);
            Cliente::where('empresa_id', $empresa->id)->where('nombre', 'Verdulerias Unidas')->update(['saldo_inicial' => round(18000 * $profile['saldo_cliente'], 2)]);
            Proveedor::where('empresa_id', $empresa->id)->where('nombre', 'Fertil Agro')->update(['saldo_inicial' => round(32000 * $profile['saldo_proveedor'], 2)]);

            Venta::where('empresa_id', $empresa->id)->orderBy('id')->get()->each(function (Venta $venta, int $ventaIndex) use ($profile, $empresa) {
                $venta->bolsas = round((425 + ($ventaIndex * 45)) * $profile['escala_ventas'], 2);
                $venta->precio_por_bolsa = round((3400 + ($ventaIndex * 160)) * $profile['escala_precio'], 2);
                $venta->peso_bolsa_kg = $empresa->peso_bolsa_kg ?: 20;
                $venta->save();
            });

            Gasto::where('empresa_id', $empresa->id)->orderBy('id')->get()->each(function (Gasto $gasto, int $gastoIndex) use ($profile) {
                $gasto->update(['importe_total' => round((95000 + ($gastoIndex * 21500)) * $profile['escala_gastos'], 2)]);
            });

            MovimientoCuenta::where('empresa_id', $empresa->id)
                ->where('tipo', 'ajuste')
                ->where('concepto', 'Ajuste de caja inicial')
                ->update(['importe' => round(15000 * $profile['escala_ventas'], 2)]);
        });
    }
}
