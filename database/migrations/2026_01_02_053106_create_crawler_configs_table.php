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
        Schema::create('crawler_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 100);
            $table->string('sources', 255)->nullable();

            $table->foreignUuid('lexicon_id')
                ->constrained('lexicons')
                ->cascadeOnDelete();

            $table->text('description')->nullable();
            $table->string('frequency_code', 50)->nullable();

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
        Schema::dropIfExists('crawler_configs');
    }
};
