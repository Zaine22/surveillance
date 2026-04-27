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
            ALTER TABLE crawler_configs
            MODIFY status ENUM('pending', 'enabled', 'disabled')
            DEFAULT 'enabled'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE crawler_configs
            MODIFY status ENUM('enabled', 'disabled')
            DEFAULT 'enabled'
        ");
    }
};
