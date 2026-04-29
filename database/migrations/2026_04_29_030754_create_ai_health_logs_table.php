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
        Schema::create('ai_health_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('checked_at');
            $table->integer('latency');
            $table->integer('cpu');
            $table->integer('memory');
            $table->string('health_status');
            $table->text('message');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_health_logs');
    }
};
