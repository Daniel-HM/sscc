<?php

namespace App\Http\Controllers;

use App\Models\Pakbonnen;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PakbonController extends Controller
{

    public function __construct(private readonly PdfController $pdfController)
    {
    }

    public function checkForPakbonFiles()
    {
        $directories = Storage::disk('local')->allDirectories();

        foreach ($directories as $directory) {
            $files = collect(Storage::disk('local')->files($directory));

            $hasRequiredFiles = $files->count() === 2 &&
                $files->filter(fn($file) => strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'xlsx'
                )->isNotEmpty() &&
                $files->filter(fn($file) => strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf'
                )->isNotEmpty();

            if ($hasRequiredFiles) {
                $date = $this->pdfController->getDateFromFirstPageOfPdf($directory, $directory);
                $this->createPakbonEntryDB($date, $directory);
            }
        }
        return true;
    }

    protected function createPakbonEntryDB($date, $directory): void
    {
        try {
            // Create a new Pakbon record
            Pakbonnen::firstOrCreate([
                'naam' => $directory,
                'movedToFolder' => true,
                'pakbonDatum' => Carbon::parse($date)->format('Y-m-d'),
            ]);

            Log::info('Pakbon entry created successfully.', ['directory' => $directory]);

        } catch (Exception $e) {
            // Log errors during Pakbon creation
            Log::error('Error creating Pakbon DB entry.', [
                'error' => $e->getMessage(),
                'directory' => $directory,
            ]);
        }
    }

    public function moveProcessedFilesToArchive()
    {

        $directories = Storage::disk('local')->allDirectories();
        Log::info("Found directories: " . json_encode($directories)); // See what directories are found

        foreach ($directories as $directory) {

            // Log the exact path we're checking
            Log::info("Checking directory: " . $directory);
            Log::info("Full path: " . Storage::disk('local')->path($directory));

            // Check if directory exists using Storage facade
            $exists = Storage::disk('local')->exists($directory);
            Log::info("Directory exists in Storage: " . ($exists ? 'yes' : 'no'));

            $pakbonExists = Pakbonnen::where([
                'naam' => $directory,
                'isVerwerkt' => true,
                'isConverted' => true
            ])->exists();
            Log::info("Pakbon exists and is processed: " . ($pakbonExists ? 'yes' : 'no'));

            if ($pakbonExists && $exists) {
                try {
                    $files = Storage::disk('local')->allFiles($directory);
                    foreach ($files as $file) {
                        $relativePath = str_replace($directory . '/', '', $file);
                        Storage::disk('archive')->put(
                            $directory . '/' . $relativePath,
                            Storage::disk('local')->get($file)
                        );
                    }
                    Storage::disk('local')->deleteDirectory($directory);
                    Log::info("Moved {$directory} to archive");
                } catch (Exception $e) {
                    Log::error("Move failed for {$directory}: " . $e->getMessage());
                }
            }
        }
    }

}
