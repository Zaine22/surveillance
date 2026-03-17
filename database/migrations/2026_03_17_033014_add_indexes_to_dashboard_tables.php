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
        Schema::table('crawler_tasks', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('status');
        });

        Schema::table('ai_predict_results', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('ai_score');
            $table->index('review_status');
        });

        Schema::table('case_management', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};