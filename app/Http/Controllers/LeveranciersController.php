<?php

namespace App\Http\Controllers;

use App\Services\DataService;
use Illuminate\Http\Request;

class LeveranciersController extends Controller
{
    public function __construct(
        private readonly DataService $dataService
    )
    {
    }

    public function show()
    {
        $leveranciers = $this->dataService->getAllLeveranciers();

        return view('leveranciers', ['leveranciers' => $leveranciers]);
    }
}
