<?php

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Gasto;
use App\Models\Tenant\Proveedor;
use App\Models\Tenant\Venta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('movimientos_cuenta')
            ->where('origen_type', Venta::class)
            ->orderBy('id')
            ->get(['id', 'origen_id'])
            ->each(function ($movimiento): void {
                $clienteId = DB::table('ventas')->where('id', $movimiento->origen_id)->value('cliente_id');

                if ($clienteId) {
                    DB::table('movimientos_cuenta')
                        ->where('id', $movimiento->id)
                        ->update([
                            'origen_type' => Cliente::class,
                            'origen_id' => $clienteId,
                        ]);
                }
            });

        DB::table('movimientos_cuenta')
            ->where('origen_type', Gasto::class)
            ->orderBy('id')
            ->get(['id', 'origen_id'])
            ->each(function ($movimiento): void {
                $proveedorId = DB::table('gastos')->where('id', $movimiento->origen_id)->value('proveedor_id');

                if ($proveedorId) {
                    DB::table('movimientos_cuenta')
                        ->where('id', $movimiento->id)
                        ->update([
                            'origen_type' => Proveedor::class,
                            'origen_id' => $proveedorId,
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
