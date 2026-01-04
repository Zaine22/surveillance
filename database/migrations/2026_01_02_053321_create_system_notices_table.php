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
        Schema::create('system_notices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->enum('status', ['pending', 'published'])
                ->default('pending');

            $table->timestamp('publish_date')->nullable();
            $table->timestamp('expire_at')->nullable();

            $table->string('title', 200)->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notices');
    }
};
