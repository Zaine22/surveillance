<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupTmpFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $folderName,
        public Carbon $scheduledFor
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Only clean up if scheduled time has passed
        if (now()->lt($this->scheduledFor)) {
            // Reschedule for later
            Log::info('Cleanup job executed too early, rescheduling', [
                'folder_name' => $this->folderName,
                'scheduled_for' => $this->scheduledFor,
                'current_time' => now(),
            ]);

            $this->release($this->scheduledFor->diffInSeconds(now()));
            return;
        }

        // Use configured tmpzip path
        $tmpBasePath = config('app.tmp_zip_path', '/mnt/tmpzip');
        $tmpZip = $tmpBasePath . '/' . $this->folderName . '.zip';
        $tmpFolder = $tmpBasePath . '/' . $this->folderName;

        Log::info('Starting tmp files cleanup', [
            'folder_name' => $this->folderName,
            'scheduled_for' => $this->scheduledFor,
            'tmp_zip' => $tmpZip,
            'tmp_folder' => $tmpFolder,
        ]);

        $deletedFiles = [];

        // Delete tmp zip file
        if (file_exists($tmpZip)) {
            unlink($tmpZip);
            $deletedFiles[] = $tmpZip;
            Log::info('Deleted tmp zip file', ['path' => $tmpZip]);
        }

        // Delete tmp folder recursively
        if (is_dir($tmpFolder)) {
            $this->recursiveRemoveDirectory($tmpFolder);
            $deletedFiles[] = $tmpFolder;
            Log::info('Deleted tmp folder', ['path' => $tmpFolder]);
        }

        if (empty($deletedFiles)) {
            Log::info('No tmp files found to clean up', [
                'folder_name' => $this->folderName,
            ]);
        } else {
            Log::info('Tmp files cleaned up successfully', [
                'folder_name' => $this->folderName,
                'scheduled_for' => $this->scheduledFor,
                'deleted_files' => $deletedFiles,
            ]);
        }
    }

    /**
     * Recursively remove a directory and all its contents
     *
     * @param string $dir Directory path to remove
     * @return void
     */
    private function recursiveRemoveDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->recursiveRemoveDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
