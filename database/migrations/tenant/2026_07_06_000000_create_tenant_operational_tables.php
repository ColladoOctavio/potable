<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('cuit')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('ubicacion')->nullable();
            $table->decimal('hectareas', 12, 2);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('cuit')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->decimal('saldo_inicial', 14, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('cuit')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->decimal('saldo_inicial', 14, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('categorias_gasto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->foreignId('categoria_gasto_id')->constrained('categorias_gasto')->restrictOnDelete();
            $table->date('fecha');
            $table->string('descripcion');
            $table->decimal('importe_total', 14, 2);
            $table->enum('estado_pago', ['pendiente', 'parcial', 'pagado'])->default('pendiente');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->date('fecha');
            $table->string('descripcion');
            $table->decimal('kilos', 14, 2);
            $table->decimal('precio_por_kg', 14, 2);
            $table->decimal('importe_total', 14, 2);
            $table->enum('estado_cobro', ['pendiente', 'parcial', 'cobrada'])->default('pendiente');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->decimal('saldo_inicial', 14, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('movimientos_cuenta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cuenta_id')->constrained('cuentas')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['ingreso', 'egreso', 'ajuste']);
            $table->string('concepto');
            $table->decimal('importe', 14, 2);
            $table->string('origen_type')->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['origen_type', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_cuenta');
        Schema::dropIfExists('cuentas');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('categorias_gasto');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('lotes');
        Schema::dropIfExists('empresas');
    }
};
