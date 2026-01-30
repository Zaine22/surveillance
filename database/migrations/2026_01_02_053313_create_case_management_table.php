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
            $table->string('keywords', 500)->nullable();

            $table->enum('status', [
                'pending', 'created', 'notified', 'moved_offline', 'auto_offline',
            ])->default('pending');

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
