<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseCsv extends Command
{
    protected $signature = 'diagnose:csv {file}';
    protected $description = 'Diagnose CSV file format issues';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $this->info("Examining file: $file");

        // First, check what the file actually looks like
        $sample = file_get_contents($file, false, null, 0, 1000);
        $this->info("First 1000 characters of the file:");
        $this->line($sample);

        // Try to detect the delimiter
        $delimiters = [',', ';', "\t", '|'];
        $delimiter = $this->detectDelimiter($file, $delimiters);

        if ($delimiter) {
            $this->info("Detected delimiter: " . ($delimiter === "\t" ? "TAB" : $delimiter));
        } else {
            $this->warn("Could not detect delimiter. Trying comma as default.");
            $delimiter = ',';
        }

        // Now try to read with the detected delimiter
        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Could not open file: $file");
            return 1;
        }

        // Read the header line
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            $this->error("Could not read headers from CSV");
            fclose($handle);
            return 1;
        }

        $headerCount = count($headers);
        $this->info("Found $headerCount columns in header row");
        $this->line("Headers: " . implode(', ', $headers));

        // Check a few rows for column counts
        $rowCounts = [];
        $rowNumber = 1; // Header was row 1

        // Sample 100 rows every 5000 rows
        $sampleRows = [];
        for ($i = 0; $i < 100; $i++) {
            $sampleRows[] = 2 + $i; // First 100 rows after header
            $sampleRows[] = 5000 + $i; // 100 rows around 5000
            $sampleRows[] = 50000 + $i; // 100 rows around 50000
            $sampleRows[] = 500000 + $i; // 100 rows around 500000
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            $colCount = count($row);
            if (!isset($rowCounts[$colCount])) {
                $rowCounts[$colCount] = 0;
            }
            $rowCounts[$colCount]++;

            // Sample some rows for detailed examination
            if (in_array($rowNumber, $sampleRows)) {
                $this->line("Row $rowNumber has $colCount columns: " . json_encode($row));
            }

            // Stop after examining 500,000 rows to avoid excessive processing
            if ($rowNumber >= 550000) {
                $this->warn("Stopped analysis after 550,000 rows");
                break;
            }
        }

        fclose($handle);

        // Report column count distribution
        $this->info("Column count distribution:");
        ksort($rowCounts);
        foreach ($rowCounts as $count => $frequency) {
            $percentage = round(($frequency / $rowNumber) * 100, 2);
            $this->line("  - $count columns: $frequency rows ($percentage%)");
        }

        // Suggest a solution
        $mostCommonCount = array_search(max($rowCounts), $rowCounts);

        if ($mostCommonCount !== $headerCount) {
            $this->warn("The most common column count ($mostCommonCount) doesn't match the header count ($headerCount)");
            $this->info("Suggestion: Try re-exporting the file from Excel with the correct delimiter settings");
        } else {
            $this->info("The most common column count matches the header count ($headerCount)");
            if (count($rowCounts) > 1) {
                $this->info("However, there are rows with different column counts that need to be fixed");
            }
        }

        return 0;
    }

    private function detectDelimiter($file, $delimiters)
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return false;
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if (!$firstLine) {
            return false;
        }

        $counts = [];
        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        arsort($counts);
        reset($counts);
        $mostFrequent = key($counts);

        return $counts[$mostFrequent] > 0 ? $mostFrequent : false;
    }
}
