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
        Schema::table('lexicon_keywords', function (Blueprint $table) {
            $table->foreignUuid('parent_id')
                ->nullable()
                ->after('lexicon_id')
                ->constrained('lexicon_keywords')
                ->cascadeOnDelete();

            $table->enum('language', ['zh', 'en', 'ja'])
                ->nullable()
                ->after('parent_id');

            $table->unique(['parent_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lexicon_keywords', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropUnique(['parent_id', 'language']);
            $table->dropColumn(['parent_id', 'language']);
        });
    }
};
