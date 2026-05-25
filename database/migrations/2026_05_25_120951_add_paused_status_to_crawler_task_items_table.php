<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crawler_task_items', function (Blueprint $table) {
            DB::statement("ALTER TABLE crawler_task_items MODIFY COLUMN status ENUM('pending', 'crawling', 'syncing', 'synced', 'paused', 'error') DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crawler_task_items', function (Blueprint $table) {
            DB::statement("ALTER TABLE crawler_task_items MODIFY COLUMN status ENUM('pending', 'crawling', 'syncing', 'synced', 'error') DEFAULT 'pending'");
        });
    }
};