<?php

namespace App\Http\Controllers;

use App\Services\ChartService;
use App\Services\DataService;

class DashboardController extends Controller
{
    protected $dataService;
    protected $chartService;

    public function __construct(DataService $dataService, ChartService $chartService)
    {
        $this->dataService = $dataService;
        $this->chartService = $chartService;
    }

    public function show()
    {
        $pakbonnen = $this->dataService->countPakbonnen();
        $sscc = $this->dataService->countSscc();
        $artikels = $this->dataService->countArtikels();
        $leveranciers = $this->dataService->countLeveranciers();
        $assortimentChart = $this->chartService->top20ArtikelsPerAssortimentsgroepChart();


        return view('dashboard', [
            'pakbonnen' => $pakbonnen,
            'sscc' => $sscc,
            'artikels' => $artikels,
            'leveranciers' => $leveranciers,
            'assortimentChart' => $assortimentChart,
        ]);
    }

    public function statistics()
    {
        $avgProductsWeek = '';
        $avgProductsMonth = '';

    }


}
