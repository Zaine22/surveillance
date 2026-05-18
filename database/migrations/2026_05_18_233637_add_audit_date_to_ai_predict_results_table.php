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
        Schema::table('ai_predict_results', function (Blueprint $table) {
            $table->timestamp('audit_date')->nullable()->after('audit_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_predict_results', function (Blueprint $table) {
            $table->dropColumn('audit_date');
        });
    }
};