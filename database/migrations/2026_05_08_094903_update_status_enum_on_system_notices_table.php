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
        // Migrate any leftover 'to_be_published' rows before changing the enum
        DB::table('system_notices')
            ->where('status', 'to_be_published')
            ->update(['status' => 'pending']);

        Schema::table('system_notices', function (Blueprint $table) {
            $table->enum('status', ['pending', 'published', 'removed'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrate 'removed' rows back to 'pending' before reverting the enum
        DB::table('system_notices')
            ->where('status', 'removed')
            ->update(['status' => 'pending']);

        Schema::table('system_notices', function (Blueprint $table) {
            $table->enum('status', ['pending', 'published', 'to_be_published'])
                ->default('pending')
                ->change();
        });
    }
};
