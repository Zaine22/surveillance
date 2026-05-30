<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'waiting' and 'retrying' to the status ENUM
        DB::statement("ALTER TABLE data_sync_records MODIFY COLUMN status ENUM('transferring', 'completed', 'failed', 'waiting', 'retrying') NOT NULL DEFAULT 'transferring'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'waiting' and 'retrying' from the status ENUM
        DB::statement("ALTER TABLE data_sync_records MODIFY COLUMN status ENUM('transferring', 'completed', 'failed') NOT NULL DEFAULT 'transferring'");
    }
};