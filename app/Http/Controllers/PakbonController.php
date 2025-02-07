<?php
namespace App\Http\Controllers;

use App\Models\Artikels;
use App\Models\Assortimentsgroep;
use App\Models\Kassagroep;
use App\Models\Leveranciers;
use App\Models\Ordertypes;
use App\Models\Pakbonnen;
use App\Models\Sscc;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;

class PakbonController extends Controller
{
    public function findCsvFiles()
    {
        $fileList    = [];
        $directories = Storage::allDirectories();
        Log::info('Starting CSV to DB operation');
        foreach ($directories as $directory) {
            $files = Storage::files($directory);
            // Filter for .csv files
            $csvFiles = array_filter($files, function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'csv';
            });
/*             // Get only filenames + ext
            $csvFileNames = array_map(function ($file) {
                return basename($file);
            }, $csvFiles);
            */

            $fileList = array_merge($fileList, $csvFiles);

        }

        $this->importCsvToDb($fileList);
        Log::info('Finished CSV to DB operation');
        return view('verwerk');
    }

    protected function getFileNameNoExtension($fileName)
    {
        return preg_replace('/\.(csv)$/', '', $fileName);
    }

    protected function importCsvToDb($fileList)
    {
        DB::beginTransaction();
        try {
            foreach ($fileList as $file) {
                $path       = storage_path('app/private/' . $file);
                $pakbonName = basename($this->getFileNameNoExtension($file));

                $pakbon = Pakbonnen::where([['naam', '=', $pakbonName], ['isVerwerkt', '=', 0]])->first();
                if (! $pakbon) {
                    Log::info($pakbonName . ' is al verwerkt, skipping');
                    continue;
                }

                $collection = (new FastExcel)->import($path);

                $kassagroepen        = [];
                $assortimentsgroepen = [];
                $ordertypes          = [];
                $leveranciers        = [];
                $artikels            = [];
                $ssccs               = [];

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
                            'artikelnummer_it'          => $line['Artikel'],
                            'artikelnummer_leverancier' => $line['Ext. Artikel'],
                            'omschrijving'              => $line['Omschrijving'],
                            'kassagroep_id'             => $kassagroepen[$line['Kassagroep']]->id,
                            'assortimentsgroep_id'      => $assortimentsgroepen[$line['Assortimentsgroep']]->id,
                            'leverancier_id'            => $leveranciers[$line['Leverancier']]->id,
                        ]
                    );
                    $now     = DB::raw('CURRENT_TIMESTAMP');
                    $ssccs[] = [
                        'sscc'         => $line['Palletnummer'],
                        'aantal_collo' => $line['Aantal Collo'],
                        'aantal_ce'    => $line['Aantal CE'],
                        'artikel_id'   => $artikels[$line['EAN']]->id,
                        'ordertype_id' => $ordertypes[$line['Ordertype']]->id,
                        'pakbon_id'    => $pakbon->id,
                        'updated_at'   => $now,
                        'created_at'   => $now,
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
    }



}
