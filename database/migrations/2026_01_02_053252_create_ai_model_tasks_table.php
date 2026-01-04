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
        Schema::create('ai_model_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('ai_model_id')
                ->constrained('ai_models')
                ->cascadeOnDelete();

            $table->foreignUuid('crawler_task_item_id')
                ->constrained('crawler_task_items')
                ->cascadeOnDelete();

            $table->string('file_name', 255)->nullable();

            $table->enum('status', ['pending', 'processing', 'completed'])
                ->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_tasks');
    }
};
