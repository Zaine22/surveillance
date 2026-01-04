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
        Schema::create('data_sync_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('source_path', 500);
            $table->string('target_path', 500);
            $table->string('file_name', 255);

            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 128)->nullable();

            $table->enum('transfer_type', [
                'rsync', 'scp', 'sftp', 'http',
            ])->default('rsync');

            $table->enum('status', [
                'pending', 'transferring', 'completed', 'failed',
            ])->default('pending');

            $table->integer('retry_count')->default(0);
            $table->integer('max_retry')->default(3);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('file_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_sync_records');
    }
};
