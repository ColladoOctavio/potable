<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('nombre');
                $table->string('slug')->unique();
                $table->string('database_name')->unique();
                $table->string('tenancy_db_name')->unique();
                $table->enum('estado', ['activo', 'suspendido', 'prueba'])->default('prueba');
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_user')) {
            Schema::create('tenant_user', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('rol')->default('administrador');
                $table->timestamps();

                $table->unique(['tenant_id', 'user_id']);
                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
    }
};
