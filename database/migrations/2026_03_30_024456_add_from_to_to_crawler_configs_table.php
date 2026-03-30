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
        Schema::table('crawler_configs', function (Blueprint $table) {
            $table->timestamp('from')->nullable()->after('frequency_code');
            $table->timestamp('to')->nullable()->after('from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crawler_configs', function (Blueprint $table) {
            $table->dropColumn(['from', 'to']);
        });
    }
};
