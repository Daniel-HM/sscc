<?php

namespace App\Imports;

use App\Models\Artikels;
use App\Models\Assortimentsgroep;
use App\Models\Leveranciers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class ArtikelsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                // Skip rows with missing required data
                if (empty($row['artikelcode']) || empty($row['artikelomschrijving_1']) || empty($row['hoofdbarcode'])) {
                    Log::warning('Artikel import: Missing required fields', $row->toArray());
                    continue;
                }

                // Process assortimentsgroep
                $assortimentsgroep = null;
                if (!empty($row['artikelgroep_naam'])) {
                    $assortimentsgroep = Assortimentsgroep::firstOrCreate(
                        ['omschrijving' => $row['artikelgroep_naam']]
                    );
                }

                // Process leverancier
                $leverancier = null;
                if (!empty($row['leveranciersnaam'])) {
                    if ($row['leveranciersnaam'] == "PLENTY GIFTS") {
                        $leverancier = Leveranciers::where('naam', 'RUIJS BROTHERS B.V. H/O PLENTY')->first();
                    } else {
                        $leverancier = Leveranciers::where('naam', $row['leveranciersnaam'])->first();
                    }

                    if (!$leverancier) {
                        Log::warning('Artikel import: Leverancier not found: ' . $row['leveranciersnaam']);
                        continue;
                    }
                } else {
                    Log::warning('Artikel import: Missing leveranciersnaam', $row->toArray());
                    continue;
                }

                // First, check if we already have this artikel by EAN
                $existing = DB::table('artikels')->where('ean', $row['hoofdbarcode'])->first();

                if ($existing) {
                    // Update existing record using DB query builder
                    DB::table('artikels')
                        ->where('ean', $row['hoofdbarcode'])
                        ->update([
                            'artikelnummer_it' => $row['artikelcode'],
                            'artikelnummer_leverancier' => $row['artikelnummer_leverancier'] ?? null,
                            'omschrijving' => $row['artikelomschrijving_1'],
                            'leverancier_id' => $leverancier->id,
                            'assortimentsgroep_id' => $assortimentsgroep ? $assortimentsgroep->id : null,
                            'verkoopprijs' => $row['verkoopprijs_incl'] ?? null,
                            'updated_at' => now(),
                        ]);
                } else {
                    // Insert new record using DB query builder
                    DB::table('artikels')->insert([
                        'artikelnummer_it' => $row['artikelcode'],
                        'artikelnummer_leverancier' => $row['artikelnummer_leverancier'] ?? null,
                        'omschrijving' => $row['artikelomschrijving_1'],
                        'leverancier_id' => $leverancier->id,
                        'assortimentsgroep_id' => $assortimentsgroep ? $assortimentsgroep->id : null,
                        'verkoopprijs' => $row['verkoopprijs_incl'] ?? null,
                        'ean' => $row['hoofdbarcode'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Artikel import error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
