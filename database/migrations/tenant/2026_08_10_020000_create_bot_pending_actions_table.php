<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_pending_actions', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 50);
            $table->string('external_user_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('type', 80);
            $table->json('payload');
            $table->enum('status', ['pending', 'completed', 'cancelled', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'external_user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_pending_actions');
    }
};
