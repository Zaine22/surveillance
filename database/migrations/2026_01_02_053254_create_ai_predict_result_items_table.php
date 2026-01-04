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
        Schema::create('ai_predict_result_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ai_predict_result_id')->constrained('ai_predict_results')->cascadeOnDelete();
            $table->string('media_url', 500)->nullable();
            $table->string('crawler_page_url', 500)->nullable();
            $table->enum('ai_result', ['normal', 'abnormal'])->nullable();
            $table->enum('status', ['valid', 'invalid'])->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('other_reason', 255)->nullable();
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->string('keywords', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_predict_result_items');
    }
};
