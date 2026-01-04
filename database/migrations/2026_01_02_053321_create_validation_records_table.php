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
        Schema::create('validation_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->enum('send_type', ['sms', 'email']);
            $table->string('send_to', 255);

            $table->enum('validate_type', ['forgate_pwd', 'login']);
            $table->string('validate_code', 255);

            $table->timestamp('expired_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_records');
    }
};
