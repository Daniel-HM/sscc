<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ExcelTypeDetector
{
    public function detectFileType($file)
    {
        // Open the file and read only the first line
        $handle = fopen($file, 'r');
        if ($handle) {
            // Get the first row (headers)
            $headers = fgetcsv($handle, 0, ';');
            fclose($handle);

            if (!$headers) {
                throw new \Exception("The uploaded file appears to be empty");
            }

            // Convert headers to lowercase for case-insensitive comparison
            $headers = array_map('strtolower', $headers);

            // Check for specific headers that identify file types
            if (in_array('leverancierscode', $headers) && in_array('leveranciersnaam', $headers)) {
                Log::info("Leverancierslijst gevonden.");
                return 'leveranciers';
            } elseif (in_array('artikelcode', $headers) && in_array('artikelomschrijving 1', $headers)) {
                Log::info("Artikellijst gevonden.");
                return 'artikels';
            } elseif (in_array('artikelnummer', $headers) && in_array('vrije_voorraad', $headers)) {
                Log::info("Voorraadlijst gevonden.");
                return 'voorraad';
            }

            // If we can't determine by headers, log what we found and throw an exception
            $headersList = implode(', ', $headers);
            throw new \Exception("Unknown file type. Headers found: {$headersList}");
        }

        throw new \Exception("Could not open the file");
    }
}
