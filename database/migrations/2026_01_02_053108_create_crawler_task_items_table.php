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
        Schema::create('crawler_task_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('task_id')
                ->constrained('crawler_tasks')
                ->cascadeOnDelete();

            $table->string('keywords', 200)->nullable();
            $table->string('crawler_machine', 50)->nullable();
            $table->string('result_file', 255)->nullable();
            $table->string('crawl_location', 2048)->nullable();
            $table->enum('status', [
                'pending', 'crawling', 'syncing', 'synced', 'error',
            ])->default('pending');

            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawler_task_items');
    }
};
