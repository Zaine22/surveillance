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
        Schema::create('lexicon_keywords', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('lexicon_id')
                ->constrained('lexicons')
                ->cascadeOnDelete();

            $table->text('keywords');
            $table->integer('crawl_hit_count')->default(0);
            $table->integer('case_count')->default(0);

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
        Schema::dropIfExists('lexicon_keywords');
    }
};
