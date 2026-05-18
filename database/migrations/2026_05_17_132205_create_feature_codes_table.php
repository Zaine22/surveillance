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
        Schema::create('feature_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->string('title')->nullable()->comment('標題 / display title');
            $table->string('feature_code')->comment('特徵碼');
            $table->text('remark')->nullable()->comment('備註');
            $table->string('image_path')->nullable()->comment('圖片');
            $table->index('title');
            $table->index('feature_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_codes');
    }
};
