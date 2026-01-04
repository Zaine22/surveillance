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
        Schema::create('crawler_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('crawler_config_id')
                ->constrained('crawler_configs')
                ->cascadeOnDelete();

            $table->foreignUuid('lexicon_id')
                ->constrained('lexicons')
                ->cascadeOnDelete();

            $table->enum('status', [
                'pending', 'processing', 'completed', 'error', 'paused', 'deleted',
            ])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawler_tasks');
    }
};
