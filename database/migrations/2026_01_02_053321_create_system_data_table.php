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
        Schema::create('system_data', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('data_code', 100)->nullable();
            $table->string('data_type', 100)->nullable();
            $table->string('data_value', 200)->nullable();
            $table->string('data_name', 200)->nullable();

            $table->integer('sort')->default(0);

            $table->enum('status', ['enabled', 'disabled'])
                ->default('enabled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_data');
    }
};
