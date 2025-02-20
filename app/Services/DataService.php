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

    public function getAllArtikels($paginated)
    {
        if ($paginated) {
            return Artikels::with(['leveranciers', 'kassagroep', 'assortimentsgroep'])->paginate($paginated);
        } else {
            return Artikels::with(['leveranciers', 'kassagroep', 'assortimentsgroep'])->get();
        }
    }

    public function getAllLeveranciers()
    {
        return Leveranciers::all();
    }

    public function getArtikelsBySscc($sscc)
    {
        return Cache::remember($sscc, now()->addHours(48), function () use ($sscc) {
            return Sscc::select('sscc.*')
                ->join('pakbonnen', 'sscc.pakbon_id', '=', 'pakbonnen.id')
                ->join('artikels', 'sscc.artikel_id', '=', 'artikels.id')
                ->join('assortimentsgroep', 'artikels.assortimentsgroep_id', '=', 'assortimentsgroep.id')
                ->join('kassagroep', 'artikels.kassagroep_id', '=', 'kassagroep.id')
                ->join('leveranciers', 'artikels.leverancier_id', '=', 'leveranciers.id')
                ->join('ordertypes', 'sscc.ordertype_id', '=', 'ordertypes.id')
                ->where('sscc.sscc', $sscc)
                ->with(['artikel', 'ordertypes'])
                ->get();
        });
    }

    public function getArtikelByEan($ean)
    {
        return Artikels::where('ean', $ean)->with('leveranciers')->first();
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
                ->orderBy('artikels.id')
                ->orderBy('sscc.sscc')
                ->get()
                ->groupBy('leveranciers.naam');
        });
    }
}
