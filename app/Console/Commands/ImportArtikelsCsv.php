<?php

namespace App\Console\Commands;

use App\Models\Assortimentsgroep;
use App\Models\Leveranciers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportArtikelsCsv extends Command
{
    protected $signature = 'import:artikels-csv {file}';
    protected $description = 'Import artikels from semicolon-delimited CSV file';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $this->info("Starting CSV import for: $file");

        // Try to open the file
        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Could not open file: $file");
            return 1;
        }

        // Read the header line with semicolon delimiter
        $headers = fgetcsv($handle, 0, ';');
        if (!$headers) {
            $this->error("Could not read headers from CSV");
            fclose($handle);
            return 1;
        }

        // Clean headers (remove BOM if present)
        $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);

        $headerCount = count($headers);
        $this->info("Found $headerCount columns: " . implode(', ', $headers));

        // Create a mapping of expected column names to their index
        $columnMap = [
            'artikelcode' => array_search('Artikelcode', $headers),
            'artikelomschrijving_1' => array_search('Artikelomschrijving 1', $headers),
            'hoofdbarcode' => array_search('Hoofdbarcode', $headers),
            'leveranciersnaam' => array_search('Leveranciersnaam', $headers),
            'artikelgroep_naam' => array_search('Artikelgroep naam', $headers),
            'verkoopprijs_incl' => array_search('Verkoopprijs incl', $headers),
            'artikelnummer_leverancier' => array_search('Artikelnummer leverancier', $headers),
        ];

        // Verify all required columns exist
        foreach ($columnMap as $key => $index) {
            if ($index === false) {
                $this->error("Required column not found: $key");
                fclose($handle);
                return 1;
            }
        }

        // Process rows in batches
        $batchSize = 500;
        $batch = [];
        $processed = 0;
        $skipped = 0;
        $updated = 0;
        $created = 0;
        $errors = 0;
        $rowNumber = 1; // Header was row 1

        $this->info("Processing CSV data in batches of $batchSize...");
        $this->info("Using semicolon (;) as delimiter...");

        $progressBar = $this->output->createProgressBar(200000); // Assuming up to 200k rows
        $progressBar->start();

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $rowNumber++;
            $progressBar->advance();

            // Skip rows with wrong column count
            if (count($row) !== $headerCount) {
                $skipped++;
                continue;
            }

            // Extract data using column mapping
            $rowData = [
                'artikelcode' => $row[$columnMap['artikelcode']],
                'artikelomschrijving_1' => $this->cleanString($row[$columnMap['artikelomschrijving_1']]),
                'hoofdbarcode' => $row[$columnMap['hoofdbarcode']],
                'leveranciersnaam' => $this->cleanString($row[$columnMap['leveranciersnaam']]),
                'artikelgroep_naam' => $this->cleanString($row[$columnMap['artikelgroep_naam']]),
                'verkoopprijs_incl' => str_replace(',', '.', $row[$columnMap['verkoopprijs_incl']]), // Fix decimal separator
                'artikelnummer_leverancier' => $row[$columnMap['artikelnummer_leverancier']],
            ];

            $batch[] = $rowData;

            // Process batch when it reaches the batch size
            if (count($batch) >= $batchSize) {
                $batchErrors = $this->processBatch($batch, $processed, $skipped, $updated, $created);
                $errors += $batchErrors;
                $batch = [];

                // Update progress display every 1000 rows
                if ($processed % 1000 === 0) {
                    $progressBar->clear();
                    $this->info("Processed $processed rows ($created created, $updated updated, $skipped skipped, $errors errors)");
                    $progressBar->display();
                }
            }
        }

        // Process any remaining rows
        if (!empty($batch)) {
            $batchErrors = $this->processBatch($batch, $processed, $skipped, $updated, $created);
            $errors += $batchErrors;
        }

        $progressBar->finish();
        fclose($handle);

        $this->newLine(2);
        $this->info("Import completed:");
        $this->info("- Total rows processed: $processed");
        $this->info("- Records created: $created");
        $this->info("- Records updated: $updated");
        $this->info("- Rows skipped: $skipped");
        $this->info("- Errors encountered: $errors");

        return 0;
    }

    private function processBatch($batch, &$processed, &$skipped, &$updated, &$created)
    {
        $batchErrors = 0;

        foreach ($batch as $rowData) {
            try {
                DB::beginTransaction();

                // Skip rows with missing required data
                if (empty($rowData['artikelcode']) || empty($rowData['artikelomschrijving_1']) || empty($rowData['hoofdbarcode'])) {
                    $skipped++;
                    DB::rollBack();
                    continue;
                }

                // Process assortimentsgroep
                $assortimentsgroep = null;
                if (!empty($rowData['artikelgroep_naam'])) {
                    $assortimentsgroep = Assortimentsgroep::firstOrCreate(
                        ['omschrijving' => $rowData['artikelgroep_naam']]
                    );
                }

                // Process leverancier
                $leverancier = null;
                if (!empty($rowData['leveranciersnaam'])) {
                    if ($rowData['leveranciersnaam'] == "PLENTY GIFTS") {
                        $leverancier = Leveranciers::where('naam', 'RUIJS BROTHERS B.V. H/O PLENTY')->first();
                    } else {
                        $leverancier = Leveranciers::where('naam', $rowData['leveranciersnaam'])->first();
                    }

                    if (!$leverancier) {
                        Log::warning('Artikel import: Leverancier not found: ' . $rowData['leveranciersnaam']);
                        $skipped++;
                        DB::rollBack();
                        continue;
                    }
                } else {
                    $skipped++;
                    DB::rollBack();
                    continue;
                }

                // Check if artikel already exists
                $exists = DB::table('artikels')->where('ean', $rowData['hoofdbarcode'])->exists();

                if ($exists) {
                    // Update existing record
                    DB::table('artikels')
                        ->where('ean', $rowData['hoofdbarcode'])
                        ->update([
                            'artikelnummer_it' => $rowData['artikelcode'],
                            'artikelnummer_leverancier' => $rowData['artikelnummer_leverancier'] ?? null,
                            'omschrijving' => $rowData['artikelomschrijving_1'],
                            'leverancier_id' => $leverancier->id,
                            'assortimentsgroep_id' => $assortimentsgroep ? $assortimentsgroep->id : null,
                            'verkoopprijs' => $rowData['verkoopprijs_incl'] ?? null,
                            'updated_at' => now(),
                        ]);

                    $updated++;
                } else {
                    // Insert new record
                    DB::table('artikels')->insert([
                        'artikelnummer_it' => $rowData['artikelcode'],
                        'artikelnummer_leverancier' => $rowData['artikelnummer_leverancier'] ?? null,
                        'omschrijving' => $rowData['artikelomschrijving_1'],
                        'leverancier_id' => $leverancier->id,
                        'assortimentsgroep_id' => $assortimentsgroep ? $assortimentsgroep->id : null,
                        'verkoopprijs' => $rowData['verkoopprijs_incl'] ?? null,
                        'ean' => $rowData['hoofdbarcode'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $created++;
                }

                $processed++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $batchErrors++;

                // Log the error but keep going with other records
                Log::error("Error importing row: " . json_encode($rowData) . " - Error: " . $e->getMessage());

                // If this is a character encoding issue, show a special message
                if (strpos($e->getMessage(), 'Incorrect string value') !== false) {
                    $this->warn("\nCharacter encoding issue detected with: " . $rowData['artikelomschrijving_1']);
                    $this->warn("Cleaned version: " . $this->cleanString($rowData['artikelomschrijving_1'], true));
                }
            }
        }

        return $batchErrors;
    }

    /**
     * Clean string from problematic characters
     */
    private function cleanString($string, $aggressive = false)
    {
        // First replace common problematic characters
        $string = str_replace(['�', '�', '�', '�'], ["'", '"', '-', '-'], $string);

        if ($aggressive) {
            // More aggressive cleaning for problematic strings
            // Remove all non-basic ASCII characters
            $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        } else {
            // Convert to UTF-8 if it's not already
            if (!mb_check_encoding($string, 'UTF-8')) {
                $string = mb_convert_encoding($string, 'UTF-8', 'Windows-1252');
            }

            // Remove any invalid UTF-8 sequences
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        }

        return $string;
    }
}
