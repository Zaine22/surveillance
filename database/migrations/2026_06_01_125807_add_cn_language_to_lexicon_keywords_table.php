<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lexicon_keywords', function (Blueprint $table) {
            // Modify the language enum to include 'cn' for Simplified Chinese
            DB::statement("ALTER TABLE lexicon_keywords MODIFY COLUMN language ENUM('zh', 'en', 'ja', 'cn') NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lexicon_keywords', function (Blueprint $table) {
            // Revert the language enum back to original values
            DB::statement("ALTER TABLE lexicon_keywords MODIFY COLUMN language ENUM('zh', 'en', 'ja') NULL");
        });
    }
};
