<?php

namespace App\Console\Commands;

use App\Models\Artikels;
use App\Models\Assortimentsgroep;
use App\Models\Leveranciers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportVoorraadCsv extends Command
{
    protected $signature = 'import:voorraad-csv {file}';
    protected $description = 'Import voorraad from semicolon-delimited CSV file';

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
            'ean' => array_search('EANCODE', $headers),
            'totale' => array_search('TOTALE_VOORRAAD', $headers),
            'vrije' => array_search('VRIJE_VOORRAAD', $headers),
            'klantorder' => array_search('KLANTORDER_VOORRAAD', $headers),
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
                'ean' => $row[$columnMap['ean']],
                'totale_voorraad' => $this->cleanString($row[$columnMap['totale_voorraad']]),
                'vrije_voorraad' => str_replace(',', '.', $row[$columnMap['vrije_voorraad']]), // Fix decimal separator
                'klantorder_voorraad' => $row[$columnMap['klantorder_voorraad']],
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
                if (empty($rowData['ean']) || $rowData['totale_voorraad'] == '' || $rowData['vrije_voorraad'] == '') {
                    $skipped++;
                    DB::rollBack();
                    continue;
                }

                // Process leverancier
                $artikel = null;
                if (!empty($rowData['ean'])) {
                    $artikel = Artikels::where('ean', $rowData['ean'])->first();

                    if (!$artikel) {
                        Log::warning('Artikel import: EAN not found: ' . $rowData['ean']);
                        $skipped++;
                        DB::rollBack();
                        continue;
                    }
                } else {
                    $skipped++;
                    DB::rollBack();
                    continue;
                }

                // Check if voorraad already exists
                $exists = DB::table('voorraad')->where('artikel_id', $artikel->id)->exists();

                if ($exists) {
                    // Update existing record
                    DB::table('voorraad')
                        ->where('artikel_id', $artikel->id)
                        ->update([
                            'totaal' => $rowData['totale_voorraad'],
                            'vrij' => $rowData['vrije_voorraad'],
                            'klantorder' => $rowData['klantorder_voorraad'],
                            'updated_at' => now(),
                        ]);

                    $updated++;
                } else {
                    // Insert new record
                    DB::table('voorraad')->insert([
                        'artikel_id' => $artikel->id,
                        'totaal' => $rowData['totale_voorraad'],
                        'vrij' => $rowData['vrije_voorraad'],
                        'klantorder' => $rowData['klantorder_voorraad'],
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
