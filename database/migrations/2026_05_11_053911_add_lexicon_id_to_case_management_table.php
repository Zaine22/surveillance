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
            $table->uuid('lexicon_id')->nullable()->after('ai_predict_result_id');

            $table->foreign('lexicon_id')
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
            $table->dropForeign(['lexicon_id']);
            $table->dropColumn('lexicon_id');
        });
    }
};
