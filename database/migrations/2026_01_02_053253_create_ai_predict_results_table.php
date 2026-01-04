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
        Schema::create('ai_predict_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ai_model_task_id')->constrained('ai_model_tasks')->cascadeOnDelete();
            $table->uuid('lexicon_id')->nullable();
            $table->string('keywords', 100)->nullable();
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->text('analysis_result')->nullable();
            $table->enum('review_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->enum('audit_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->enum('ai_analysis_result', ['normal', 'abnormal'])->nullable();
            $table->json('ai_analysis_detail')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_predict_results');
    }
};
