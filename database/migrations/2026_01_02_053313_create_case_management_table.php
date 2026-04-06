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
        Schema::create('case_management', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('ai_predict_result_id')
                ->nullable()
                ->constrained('ai_predict_results')
                ->nullOnDelete();

            $table->string('internal_case_no', 100)->nullable();
            $table->string('external_case_no', 100)->nullable();
            $table->json('keywords')->nullable();

            $table->enum('status', [
                'pending_notification',
                'notified',
                'case_established', //created after ai_predict_result is approved
                'case_not_established',
                'tracking_completed',
                'external_pending',
            ])->default('pending_notification');

            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_management');
    }
};
