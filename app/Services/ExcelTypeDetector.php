<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ExcelTypeDetector
{
    public function detectFileType($file)
    {
        // Load the first few rows to examine headers
        $data = Excel::toArray([], $file)[0];

        if (empty($data)) {
            throw new \Exception("The uploaded file appears to be empty");
        }

        // Get headers (first row)
        $headers = array_map('strtolower', $data[0]);

        // Check for specific headers that identify file types
        if (in_array('leverancierscode', $headers) && in_array('leveranciersnaam', $headers)) {
            Log::info("Leverancierslijst gevonden.");
            return 'leveranciers';
        } elseif (in_array('artikelcode', $headers) && in_array('artikelomschrijving 1', $headers)) {
            Log::info("Artikellijst gevonden.");
            return 'artikels';
        } elseif (in_array('artikelnummer', $headers) && in_array('vrije voorraad', $headers)) {
            Log::info("Voorraadlijst gevonden.");
            return 'voorraad';
        }

        // If we can't determine by headers, log what we found and throw an exception
        $headersList = implode(', ', $headers);
        throw new \Exception("Unknown file type. Headers found: {$headersList}");
    }
}
