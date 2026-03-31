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
        Schema::table('system_notices', function (Blueprint $table) {
            $table->enum('status', ['pending', 'published', 'to_be_published'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_notices', function (Blueprint $table) {

            \Illuminate\Support\Facades\DB::table('system_notices')
                ->where('status', 'to_be_published')
                ->update(['status' => 'pending']);

            $table->enum('status', ['pending', 'published'])
                ->default('pending')
                ->change();
        });
    }
};
