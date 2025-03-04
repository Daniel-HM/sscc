<?php

namespace App\Http\Controllers;

use App\Models\Pakbonnen;
use App\Services\DataService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class PakbonController extends Controller
{

    private $dataService;

    public function __construct(private readonly PdfController $pdfController, DataService $dataService)
    {
        $this->dataService = $dataService;
    }


    public function list()
    {
        $pakbonnen = $this->dataService->getallPakbonnen()->sortByDesc('pakbonDatum');
        return view('pakbonnen', ['pakbonnen' => $pakbonnen]);
    }

    // Show Pakbon contents
    public function show($pakbon)
    {
        $pakbonNaam = $pakbon;
        // Invalid format check
        if (!preg_match('/^SPS-\d{8}$/', $pakbon)) {
            return redirect()->route('pakbonnen.list')
                ->withErrors(['error' => 'Pakbon niet gevonden.']);
        }

        // Get and check data
        $artikels = collect($this->dataService->getArtikelsByPakbon($pakbon));
        if ($artikels->isEmpty()) {
            return redirect()->route('pakbonnen.list')
                ->withErrors(['error' => 'Pakbon niet gevonden.']);
        }
        // Success case
        return view('result', [
            'data' => $artikels,
            'type' => 'sps',
            'barcode' => $pakbonNaam,
            'table' => true
        ]);
    }

    public function checkForPakbonFiles()
    {
        $directories = Storage::disk('local')->allDirectories();
        if ($directories) {
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
        }
        Log::info('No pakbon files, so no entries to be made');
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

    public function moveProcessedFilesToArchive(): bool
    {
        Log::info('Reached moveProcessedFilesToArchive.');
        $directories = Storage::disk('local')->allDirectories();

        if ($directories) {
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
            Log::info('Everything that needed moving, has been moved.');
            return true;
        }

        Log::info('No directories to move to archive.');
        return true;
    }

}
