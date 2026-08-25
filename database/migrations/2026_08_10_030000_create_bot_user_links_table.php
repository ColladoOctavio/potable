<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_user_links', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 50);
            $table->string('external_user_id');
            $table->string('external_username')->nullable();
            $table->string('external_display_name')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_id');
            $table->unsignedBigInteger('empresa_id');
            $table->boolean('active')->default(true);
            $table->timestamp('linked_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'external_user_id']);
            $table->index(['user_id', 'tenant_id', 'empresa_id']);
            $table->index(['channel', 'active']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_user_links');
    }
};
