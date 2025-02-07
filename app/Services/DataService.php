<?php

namespace App\Services;

use App\Models\Artikels;
use App\Models\Leveranciers;
use App\Models\Pakbonnen;
use App\Models\Sscc;
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

    public function getAllArtikels()
    {
        return Artikels::all();
    }

    public function getAllLeveranciers()
    {
        return Leveranciers::all();
    }

    public function getArtikelsBySscc($sscc)
    {
        return Sscc::select('sscc.*')
            ->join('artikels', 'sscc.artikel_id', '=', 'artikels.id')
            ->join('assortimentsgroep', 'artikels.assortimentsgroep_id', '=', 'assortimentsgroep.id')
            ->join('kassagroep', 'artikels.kassagroep_id', '=', 'kassagroep.id')
            ->join('leveranciers', 'artikels.leverancier_id', '=', 'leveranciers.id')
            ->join('ordertypes', 'sscc.ordertype_id', '=', 'ordertypes.id')
            ->where('sscc.sscc', $sscc)
            ->with(['artikel', 'ordertypes'])
            ->get();
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

            ->select('sscc', DB::raw('count(*) as total'))

            ->groupBy('sscc')

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
}
