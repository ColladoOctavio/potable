<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $convertedFromKilos = false;

        Schema::table('empresas', function (Blueprint $table) {
            if (! Schema::hasColumn('empresas', 'peso_bolsa_kg')) {
                $table->decimal('peso_bolsa_kg', 8, 2)->default(20)->after('direccion');
            }
        });

        Schema::table('ventas', function (Blueprint $table) use (&$convertedFromKilos) {
            if (Schema::hasColumn('ventas', 'kilos') && ! Schema::hasColumn('ventas', 'bolsas')) {
                $table->renameColumn('kilos', 'bolsas');
                $convertedFromKilos = true;
            }

            if (Schema::hasColumn('ventas', 'precio_por_kg') && ! Schema::hasColumn('ventas', 'precio_por_bolsa')) {
                $table->renameColumn('precio_por_kg', 'precio_por_bolsa');
            }
        });

        Schema::table('ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas', 'peso_bolsa_kg')) {
                $table->decimal('peso_bolsa_kg', 8, 2)->default(20)->after('precio_por_bolsa');
            }
        });

        if ($convertedFromKilos) {
            DB::table('ventas')->update([
                'bolsas' => DB::raw('bolsas / 20'),
                'precio_por_bolsa' => DB::raw('precio_por_bolsa * 20'),
                'peso_bolsa_kg' => 20,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ventas')->update([
            'bolsas' => DB::raw('bolsas * COALESCE(NULLIF(peso_bolsa_kg, 0), 20)'),
            'precio_por_bolsa' => DB::raw('precio_por_bolsa / COALESCE(NULLIF(peso_bolsa_kg, 0), 20)'),
        ]);

        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'peso_bolsa_kg')) {
                $table->dropColumn('peso_bolsa_kg');
            }
        });

        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'bolsas') && ! Schema::hasColumn('ventas', 'kilos')) {
                $table->renameColumn('bolsas', 'kilos');
            }

            if (Schema::hasColumn('ventas', 'precio_por_bolsa') && ! Schema::hasColumn('ventas', 'precio_por_kg')) {
                $table->renameColumn('precio_por_bolsa', 'precio_por_kg');
            }
        });

        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'peso_bolsa_kg')) {
                $table->dropColumn('peso_bolsa_kg');
            }
        });
    }
};
