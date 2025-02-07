<?php
namespace App\Http\Controllers;

use App\Services\DataService;

class DashboardController extends Controller
{
    protected $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function show()
    {
        $pakbonnen = $this->dataService->countPakbonnen();
        $sscc      = $this->dataService->countSscc();
        $artikels  = $this->dataService->countArtikels();
        $leveranciers = $this->dataService->countLeveranciers();

        return view('dashboard', [
            'pakbonnen' => $pakbonnen,
            'sscc'      => $sscc,
            'artikels'  => $artikels,
            'leveranciers' => $leveranciers
        ]);
    }

}
