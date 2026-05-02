<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_health_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('ai_model_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamp('checked_at');

            $table->float('cpu_usage')->nullable();
            $table->float('ram_usage')->nullable();
            $table->float('gpu_usage')->nullable();
            $table->json('metrics')->nullable();
            $table->enum('health_status', [
                'stable',
                'slightly_busy',
                'busy',
                'error',
            ])->default('stable');
            $table->text('message')->nullable();

            $table->timestamps();
            $table->index(['ai_model_id', 'checked_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_health_logs');
    }
};
