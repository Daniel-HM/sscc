<?php

namespace App\Services;

use App\Models\Artikels;
use App\Models\Leveranciers;
use App\Models\Pakbonnen;
use App\Models\Sscc;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataService
{
    public function getAllPakbonnen()
    {
        return Pakbonnen::all();
    }

    public function getAllSscc()
    {
        return Sscc::with('artikel')->get();
    }

    public function getAllArtikels($paginated, $orderBy = 'artikels.omschrijving', $direction = 'asc')
    {
        $query = Artikels::with(['leveranciers', 'kassagroep', 'assortimentsgroep', 'voorraad']);

        // Handle ordering by relationship column
        if (strpos($orderBy, '.') !== false) {
            [$relation, $column] = explode('.', $orderBy);

            // Define join tables and conditions for each relation
            $relationMap = [
                'voorraad' => [
                    'table' => 'voorraad',
                    'first' => 'artikels.id',
                    'operator' => '=',
                    'second' => 'voorraad.artikel_id'
                ],
                'leveranciers' => [
                    'table' => 'leveranciers',
                    'first' => 'artikels.leverancier_id',
                    'operator' => '=',
                    'second' => 'leveranciers.id'
                ],
                'assortimentsgroep' => [
                    'table' => 'assortimentsgroep',
                    'first' => 'artikels.assortimentsgroep_id',
                    'operator' => '=',
                    'second' => 'assortimentsgroep.id'
                ],
                'kassagroep' => [
                    'table' => 'kassagroep',
                    'first' => 'artikels.kassagroep_id',
                    'operator' => '=',
                    'second' => 'kassagroep.id'
                ]
            ];

            if (isset($relationMap[$relation])) {
                $join = $relationMap[$relation];

                // Add the join if not already added
                $query->leftJoin(
                    $join['table'],
                    $join['first'],
                    $join['operator'],
                    $join['second']
                );

                // Use an alias for the main table to avoid column ambiguity
                $query->select('artikels.*')
                    ->orderBy("$relation.$column", $direction);
            } else {
                $query->orderBy($orderBy, $direction);
            }
        } else {
            $query->orderBy($orderBy, $direction);
        }

        if ($paginated) {
            return $query->paginate($paginated);
        } else {
            return $query->get();
        }
    }

    public function getAllLeveranciers($paginated)
    {
        $query = Leveranciers::orderBy('naam', 'asc');

        if ($paginated) {
            return $query->paginate($paginated);
        } else {
            return $query->get();
        }
    }

    public function getArtikelsBySscc($sscc)
    {
        return Cache::remember($sscc, now()->addHours(48), function () use ($sscc) {
            return Sscc::select(
                'sscc.*',
                'leveranciers.naam as leverancier_naam',
                'sscc.aantal_ce as aantal_ce',
                'ordertypes.omschrijving as ordertype',
                'artikels.omschrijving',
                'artikels.ean',
                'artikels.id as artikel_id'
            )
                ->join('pakbonnen', 'sscc.pakbon_id', '=', 'pakbonnen.id')
                ->join('artikels', 'sscc.artikel_id', '=', 'artikels.id')
                ->join('assortimentsgroep', 'artikels.assortimentsgroep_id', '=', 'assortimentsgroep.id')
                ->join('kassagroep', 'artikels.kassagroep_id', '=', 'kassagroep.id')
                ->join('leveranciers', 'artikels.leverancier_id', '=', 'leveranciers.id')
                ->join('ordertypes', 'sscc.ordertype_id', '=', 'ordertypes.id')
                ->where('sscc.sscc', $sscc)
                ->with(['artikels', 'ordertypes'])
                ->orderBy('artikels.omschrijving', 'asc')
                ->get()
                ->groupBy('leverancier_naam');
        });
    }

    public function getArtikelByEan($ean)
    {
        return Artikels::where('ean', $ean)->with(['leveranciers', 'voorraad'])->first();
    }

    public function countArtikels()
    {
        return Artikels::count();
    }

    public function countSscc()
    {
        return DB::table('sscc')
            ->select('sscc')
            ->distinct('sscc')
            ->get();
    }

    public function countPakbonnen()
    {
        return Pakbonnen::count();
    }

    public function countLeveranciers()
    {
        return Leveranciers::count();
    }

    public function getArtikelsByPakbon($sps)
    {
        return Cache::remember($sps, now()->addHours(48), function () use ($sps) {
            return Artikels::select('artikels.*', 'sscc.aantal_ce as aantal_ce', 'ordertypes.omschrijving as ordertype')
                ->join('sscc', 'artikels.id', '=', 'sscc.artikel_id')
                ->join('pakbonnen', 'sscc.pakbon_id', '=', 'pakbonnen.id')
                ->join('ordertypes', 'sscc.ordertype_id', '=', 'ordertypes.id')
                ->join('leveranciers', 'artikels.leverancier_id', '=', 'leveranciers.id')
                ->where('pakbonnen.naam', $sps)
                ->orderBy('artikels.omschrijving', 'asc')
                ->get()
                ->groupBy('leveranciers.naam');
        });
    }
}
