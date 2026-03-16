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
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->bigIncrements('record_no');
            $table->uuid('id')->unique();
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('operator_name')->nullable();
            $table->string('operator_email')->nullable();
            $table->string('department')->nullable();
            $table->string('role')->nullable();
            $table->string('page_url');
            $table->string('action');
            $table->enum('status', [
                'success',
                'failed',
            ]);
            $table->ipAddress('ip_address')->nullable();
            $table->string('token')->nullable();
            $table->integer('cost_time')->nullable();
            $table->json('page_data')->nullable();
            $table->json('request_payload')->nullable();
            $table->timestamp('operation_time')->nullable();

            $table->timestamps();
            $table->index('user_id');
            $table->index('operation_time');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
    }
};