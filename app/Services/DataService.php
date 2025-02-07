<?php
namespace App\Services;

use App\Models\Artikels;
use App\Models\Pakbonnen;
use App\Models\Sscc;

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
}
