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
        $pakbonnen = $this->dataService->getAllPakbonnen();
        $sscc      = $this->dataService->getAllSscc()->groupBy('sscc');
        $artikels  = $this->dataService->getAllArtikels();

        return view('dashboard', [
            'pakbonnen' => $pakbonnen,
            'sscc'      => $sscc,
            'artikels'  => $artikels
        ]);
    }

}
