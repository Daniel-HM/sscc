<?php

namespace App\Jobs;

use App\Http\Controllers\ExcelController;
use App\Models\FileUploads;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 6000;
    protected $fileUploadId;

    public function __construct($fileUploadId)
    {
        $this->fileUploadId = $fileUploadId;
    }

    public function handle(): void
    {
        // Get the file upload record
        $fileUpload = FileUploads::findOrFail($this->fileUploadId);

        // Get the file path
        $relativePath = 'Uploads/' . $fileUpload->filename;
        $filePath = Storage::disk('local')->path($relativePath);
        Log::info("Processing uploaded filepath: {$filePath}");
        // Check if file exists
        if (Storage::disk('local')->exists($relativePath)) {
            ProcessExcelToDatabase::dispatch($filePath);
        }
    }
}
