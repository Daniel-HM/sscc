<?php

namespace App\Http\Controllers;

use App\Imports\ArtikelsImport;
use App\Imports\LeveranciersImport;
use App\Models\Leveranciers;
use App\Services\ExcelTypeDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{

    protected $typeDetector;

    public function __construct(ExcelTypeDetector $typeDetector)
    {
        $this->typeDetector = $typeDetector;
    }

    public function importExcelFile($file)
    {
        Log::info("Detecting if file is voorraad, leveranciers, or artikels.");
        try {
            // Detect file type
            $fileType = $this->typeDetector->detectFileType($file);
            // Import based on file type
            switch ($fileType) {
                case 'leveranciers':
                    Excel::import(new LeveranciersImport, $file);
                    Log::info('success', ['Leveranciers successfully imported.']);
                    break;
                case 'artikels':
                    Excel::import(new ArtikelsImport, $file);
                    Log::info('success', ['Artikels successfully imported.']);
                    break;
                case 'voorraad':
                    // Excel::import(new VoorraadImport, $file);
                    Log::info('success', ['Voorraad successfully imported.']);
                    break;
                default:
                    Log::error('error', ['Unsupported file type.']);
            }
        } catch (\Exception $e) {
            Log::error('error', ['Error: ' . $e->getMessage()]);
        }
    }

}
