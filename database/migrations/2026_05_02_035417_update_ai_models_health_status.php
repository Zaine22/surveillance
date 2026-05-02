<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
        UPDATE ai_models
        SET health_status = 'stable'
        WHERE health_status = 'normal'
    ");

        DB::statement("
        ALTER TABLE ai_models
        MODIFY health_status ENUM(
            'stable',
            'slightly_busy',
            'busy',
            'error'
        ) NULL
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
