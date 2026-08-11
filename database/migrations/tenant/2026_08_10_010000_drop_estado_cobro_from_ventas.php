<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'estado_cobro')) {
                $table->dropColumn('estado_cobro');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas', 'estado_cobro')) {
                $table->enum('estado_cobro', ['pendiente', 'parcial', 'cobrada'])->default('pendiente')->after('importe_total');
            }
        });
    }
};
