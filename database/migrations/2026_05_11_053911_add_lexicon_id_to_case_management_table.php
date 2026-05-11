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
        Schema::table('case_management', function (Blueprint $table) {
            $table->uuid('external_lexicon_id')->nullable();

            $table->foreign('external_lexicon_id')
                ->references('id')
                ->on('lexicons')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_management', function (Blueprint $table) {
            $table->dropForeign(['external_lexicon_id']);
            $table->dropColumn('external_lexicon_id');
        });
    }
};
