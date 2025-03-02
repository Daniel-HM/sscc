<?php

namespace App\Http\Controllers;

use App\Services\DataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeveranciersController extends Controller
{
    public function __construct(
        private readonly DataService $dataService
    )
    {
    }

    public function show(): View
    {
        $leveranciers = $this->dataService->getAllLeveranciers(50);

        return view('leveranciers', ['leveranciers' => $leveranciers]);
    }
}
