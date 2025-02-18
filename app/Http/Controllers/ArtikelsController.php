<?php

namespace App\Http\Controllers;

use App\Services\DataService;
use Illuminate\Http\Request;

class ArtikelsController extends Controller
{
    public function __construct(
        private readonly DataService $dataService
    )
    {
    }

    public function show($page = null){
        $artikels = $this->dataService->getAllArtikels(30);

        return view('artikels', compact('artikels'));
    }

    public function getArtikel($ean)
    {
        $data = $this->dataService->getArtikelByEan($ean);
        return view('artikel', ['data' => $data]);
    }
}
