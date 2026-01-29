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
        Schema::create('case_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('case_id')->index();
            $table->string('url');

            $table->boolean('is_illegal');
            $table->string('legal_basis')->nullable();
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_feedback');
    }
};
