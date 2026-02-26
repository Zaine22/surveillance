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
        Schema::create('ai_predict_result_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('ai_predict_result_id')
                ->constrained('ai_predict_results')
                ->cascadeOnDelete();

            $table->foreignUuid('auditor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('decision', ['approved', 'rejected', 'partial']);

            $table->integer('valid_count');
            $table->integer('invalid_count');

            $table->text('summary')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_predict_result_audits');
    }
};
