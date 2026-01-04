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
        Schema::create('bot_machines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('version', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('health_checked_at')->nullable();
            $table->json('content')->nullable();
            $table->enum('health_status', ['busy', 'stable', 'slightly_busy', 'normal'])->nullable();
            $table->enum('status', ['enabled', 'disabled'])->default('enabled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_machines');
    }
};
