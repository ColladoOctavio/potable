<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\CategoriaGasto;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Empresa;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\Lote;
use App\Models\Tenant\MovimientoCuenta;
use App\Models\Tenant\Proveedor;
use App\Models\Tenant\Venta;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Empresa::query()->exists()) {
            return;
        }

        $empresas = collect([
            Empresa::create(['nombre' => 'Campo La Papa SA', 'cuit' => '30-71504011-8', 'email' => 'admin@lapapa.test', 'telefono' => '299 455-1001', 'direccion' => 'Ruta 22 km 1140']),
            Empresa::create(['nombre' => 'Agro Andina SRL', 'cuit' => '30-71022014-4', 'email' => 'info@agroandina.test', 'telefono' => '299 455-1002', 'direccion' => 'Parque productivo norte']),
            Empresa::create(['nombre' => 'Productores del Valle', 'cuit' => '30-70881010-1', 'email' => 'cuentas@valle.test', 'telefono' => '299 455-1003', 'direccion' => 'Colonia rural 3']),
        ]);

        $categorias = ['Semilla', 'Fertilizantes', 'Agroquímicos', 'Mano de obra', 'Maquinaria', 'Combustible', 'Riego', 'Transporte', 'Alquiler', 'Mantenimiento', 'Servicios', 'Otros'];

        foreach ($empresas as $empresaIndex => $empresa) {
            $perfil = [
                ['escala_ventas' => 1.00, 'escala_gastos' => 1.00, 'escala_precio' => 1.00, 'saldo_cuenta' => 120000, 'saldo_cliente' => 1.00, 'saldo_proveedor' => 1.00],
                ['escala_ventas' => 0.68, 'escala_gastos' => 0.74, 'escala_precio' => 0.96, 'saldo_cuenta' => 85000, 'saldo_cliente' => 0.65, 'saldo_proveedor' => 0.80],
                ['escala_ventas' => 1.34, 'escala_gastos' => 1.18, 'escala_precio' => 1.08, 'saldo_cuenta' => 175000, 'saldo_cliente' => 1.35, 'saldo_proveedor' => 1.20],
            ][$empresaIndex] ?? ['escala_ventas' => 1.00, 'escala_gastos' => 1.00, 'escala_precio' => 1.00, 'saldo_cuenta' => 120000, 'saldo_cliente' => 1.00, 'saldo_proveedor' => 1.00];

            $lotes = collect([
                Lote::create(['empresa_id' => $empresa->id, 'nombre' => 'Lote Norte', 'ubicacion' => 'Sector A', 'hectareas' => round(28 * $perfil['escala_ventas'], 2)]),
                Lote::create(['empresa_id' => $empresa->id, 'nombre' => 'Lote Sur', 'ubicacion' => 'Sector B', 'hectareas' => round(34 * $perfil['escala_ventas'], 2)]),
            ]);

            $clientes = collect([
                Cliente::create(['empresa_id' => $empresa->id, 'nombre' => 'Mercado Central Patagonia', 'cuit' => '30-62220111-2', 'telefono' => '299 500-1100', 'saldo_inicial' => round(45000 * $perfil['saldo_cliente'], 2)]),
                Cliente::create(['empresa_id' => $empresa->id, 'nombre' => 'Distribuidora Fresca', 'cuit' => '30-62220112-0', 'telefono' => '299 500-1101', 'saldo_inicial' => 0]),
                Cliente::create(['empresa_id' => $empresa->id, 'nombre' => 'Verdulerias Unidas', 'cuit' => '30-62220113-9', 'telefono' => '299 500-1102', 'saldo_inicial' => round(18000 * $perfil['saldo_cliente'], 2)]),
            ]);

            $proveedores = collect([
                Proveedor::create(['empresa_id' => $empresa->id, 'nombre' => 'Semillas del Sur', 'cuit' => '30-68880111-9', 'telefono' => '299 600-2100', 'saldo_inicial' => 0]),
                Proveedor::create(['empresa_id' => $empresa->id, 'nombre' => 'Fertil Agro', 'cuit' => '30-68880112-7', 'telefono' => '299 600-2101', 'saldo_inicial' => round(32000 * $perfil['saldo_proveedor'], 2)]),
                Proveedor::create(['empresa_id' => $empresa->id, 'nombre' => 'Transporte Ruta Fria', 'cuit' => '30-68880113-5', 'telefono' => '299 600-2102', 'saldo_inicial' => 0]),
            ]);

            $categoriasEmpresa = collect($categorias)->map(fn (string $nombre) => CategoriaGasto::create([
                'empresa_id' => $empresa->id,
                'nombre' => $nombre,
                'descripcion' => 'Gastos de '.$nombre,
            ]));

            $cuenta = $empresa->cuenta;
            $cuenta->update(['saldo_inicial' => $perfil['saldo_cuenta']]);

            for ($i = 0; $i < 8; $i++) {
                $venta = Venta::create([
                    'empresa_id' => $empresa->id,
                    'cliente_id' => $clientes->random()->id,
                    'lote_id' => $lotes->random()->id,
                    'fecha' => now()->subDays(42 - ($i * 4))->toDateString(),
                    'descripcion' => 'Venta de papa consumo '.($i + 1),
                    'kilos' => round((8500 + ($i * 900)) * $perfil['escala_ventas'], 2),
                    'precio_por_kg' => round((170 + ($i * 8)) * $perfil['escala_precio'], 2),
                    'estado_cobro' => ['cobrada', 'parcial', 'pendiente'][$i % 3],
                ]);

                if ($venta->estado_cobro !== 'pendiente') {
                    MovimientoCuenta::create([
                        'empresa_id' => $empresa->id,
                        'cuenta_id' => $cuenta->id,
                        'fecha' => $venta->fecha,
                        'tipo' => 'ingreso',
                        'concepto' => 'Cobro '.$venta->descripcion,
                        'importe' => $venta->estado_cobro === 'cobrada' ? $venta->importe_total : round($venta->importe_total * .45, 2),
                        'origen_type' => Venta::class,
                        'origen_id' => $venta->id,
                    ]);
                }
            }

            for ($i = 0; $i < 9; $i++) {
                $gasto = Gasto::create([
                    'empresa_id' => $empresa->id,
                    'proveedor_id' => $proveedores->random()->id,
                    'lote_id' => $lotes->random()->id,
                    'categoria_gasto_id' => $categoriasEmpresa->random()->id,
                    'fecha' => now()->subDays(50 - ($i * 5))->toDateString(),
                    'descripcion' => 'Gasto operativo '.($i + 1),
                    'importe_total' => round((95000 + ($i * 21500)) * $perfil['escala_gastos'], 2),
                    'estado_pago' => ['pagado', 'parcial', 'pendiente'][$i % 3],
                ]);

                if ($gasto->estado_pago !== 'pendiente') {
                    MovimientoCuenta::create([
                        'empresa_id' => $empresa->id,
                        'cuenta_id' => $cuenta->id,
                        'fecha' => $gasto->fecha,
                        'tipo' => 'egreso',
                        'concepto' => 'Pago '.$gasto->descripcion,
                        'importe' => $gasto->estado_pago === 'pagado' ? $gasto->importe_total : round($gasto->importe_total * .5, 2),
                        'origen_type' => Gasto::class,
                        'origen_id' => $gasto->id,
                    ]);
                }
            }

            MovimientoCuenta::create([
                'empresa_id' => $empresa->id,
                'cuenta_id' => $cuenta->id,
                'fecha' => now()->subDays(3)->toDateString(),
                'tipo' => 'ajuste',
                'concepto' => 'Ajuste de caja inicial',
                'importe' => round(15000 * $perfil['escala_ventas'], 2),
            ]);
        }
    }
}
