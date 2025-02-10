<?php

namespace App\Http\Controllers;

use App\Models\Artikels;
use App\Models\Assortimentsgroep;
use App\Models\Kassagroep;
use App\Models\Leveranciers;
use App\Models\Ordertypes;
use App\Models\Pakbonnen;
use App\Models\Sscc;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;

class CsvController extends Controller
{

    public function __construct(
        private readonly FastExcel $fastExcel
    )
    {
    }

    public function convertXlsxToCsv()
    {
        $directories = Storage::allDirectories();
        foreach ($directories as $directory) {
            $files = Storage::allFiles($directory);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'xlsx') {

                    $xlsx = Storage::disk('local')->path($file);
                    $csv = storage_path('app/private/' . $this->extractBaseName($file) . '.csv');
                    Log::info('Attempting to convert ' . $file . ' to a .csv file.');

                    try {
                        if (!Storage::disk('local')->path($file)) {
                            throw new Exception("File not found: {$xlsx}");
                        }

                        if (File::exists($csv)) {
                            Log::info("File {$csv} already exists.");
                            continue;
                        }
                        Log::info("Starting conversion of {$this->extractBaseName($file)}.xlsx to .csv.", context: ['directory' => $directory]);
                        // Read the .xlsx file into a collection
                        $collection = $this->fastExcel->import($xlsx);

                        // Export the collection to a .csv file
                        $convert = $this->fastExcel->data($collection)->export($csv);

                        if (!$convert) {
                            throw new Exception('Failed to convert {inputFile}');
                        }
                        $this->setConvertedToTrue($directory);

                        Log::info(
                            message: "Successfully converted {$file} to CSV",
                            context: [
                                'directory' => $directory,
                                'output' => $csv
                            ]
                        );

                    } catch (Exception $e) {
                        Log::error(
                            message: "Failed to convert {$file} to CSV",
                            context: [
                                'error' => $e->getMessage(),
                                'directory' => $directory,
                                'file' => $file
                            ]
                        );

                        throw $e;
                    }
                }
            }

        }
        return true;


    }

    public function processCsvFiles()
    {
        $fileList = [];
        $directories = Storage::allDirectories();
        if ($directories) {
            Log::info('Starting CSV to DB operation');
            foreach ($directories as $directory) {
                $files = Storage::files($directory);
                // Filter for .csv files
                $csvFiles = array_filter($files, function ($file) {
                    return pathinfo($file, PATHINFO_EXTENSION) === 'csv';
                });

                $fileList = array_merge($fileList, $csvFiles);

            }

            if ($this->importCsvToDb($fileList)) {
                Log::info('Finished CSV to DB operation');
                return true;
            }
        }
        Log::info('No directories found, skipping CSV processing.');
        return true;
    }

    protected function importCsvToDb($fileList)
    {
        DB::beginTransaction();
        try {
            foreach ($fileList as $file) {
                $path = storage_path('app/private/' . $file);
                $pakbonName = pathinfo(basename($file), PATHINFO_FILENAME);

                $pakbon = Pakbonnen::where([['naam', '=', $pakbonName], ['isVerwerkt', '=', 0]])->first();
                if (!$pakbon) {
                    Log::info($pakbonName . ' is al verwerkt, skipping');
                    continue;
                }

                $collection = (new FastExcel)->import($path);

                $kassagroepen = [];
                $assortimentsgroepen = [];
                $ordertypes = [];
                $leveranciers = [];
                $artikels = [];
                $ssccs = [];

                foreach ($collection as $line) {
                    $kassagroepen[$line['Kassagroep']] = Kassagroep::firstOrCreate(['omschrijving' => $line['Kassagroep']],
                        ['omschrijving' => $line['Kassagroep']]
                    );

                    $assortimentsgroepen[$line['Assortimentsgroep']] = Assortimentsgroep::firstOrCreate(['omschrijving' => $line['Assortimentsgroep']],
                        ['omschrijving' => $line['Assortimentsgroep']]
                    );

                    $ordertypes[$line['Ordertype']] = Ordertypes::firstOrCreate(['omschrijving' => $line['Ordertype']],
                        ['omschrijving' => $line['Ordertype']]
                    );

                    $leveranciers[$line['Leverancier']] = Leveranciers::firstOrCreate(['naam' => $line['Leverancier']],
                        ['naam' => $line['Leverancier']]
                    );

                    $artikels[$line['EAN']] = Artikels::firstOrCreate(
                        ['ean' => $line['EAN']],
                        [
                            'artikelnummer_it' => $line['Artikel'],
                            'artikelnummer_leverancier' => $line['Ext. Artikel'],
                            'omschrijving' => $line['Omschrijving'],
                            'kassagroep_id' => $kassagroepen[$line['Kassagroep']]->id,
                            'assortimentsgroep_id' => $assortimentsgroepen[$line['Assortimentsgroep']]->id,
                            'leverancier_id' => $leveranciers[$line['Leverancier']]->id,
                        ]
                    );
                    $now = DB::raw('CURRENT_TIMESTAMP');
                    $ssccs[] = [
                        'sscc' => $line['Palletnummer'],
                        'aantal_collo' => $line['Aantal Collo'],
                        'aantal_ce' => $line['Aantal CE'],
                        'artikel_id' => $artikels[$line['EAN']]->id,
                        'ordertype_id' => $ordertypes[$line['Ordertype']]->id,
                        'pakbon_id' => $pakbon->id,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ];
                }

                Sscc::insert($ssccs);

                $pakbon->isVerwerkt = 1;
                $pakbon->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return true;
    }

    /**
     * Extract the base name (e.g., 'SPS-00145524') from the filename.
     */
    private function extractBaseName($fileName): string
    {
        return preg_replace('/\.(xlsx|pdf)$/', '', $fileName);
    }

    protected function setConvertedToTrue($entry): void
    {
        try {
            Pakbonnen::where('naam', $entry)->update(['isConverted' => 1]);

            Log::info('Pakbon entry "isConverted" set to true for ' . $entry);

        } catch (Exception $e) {
            Log::error('Error updating pakbon entry - converted to true failed', [
                'error' => $e->getMessage(),
                'entry' => $entry,
            ]);
        }
    }
}
