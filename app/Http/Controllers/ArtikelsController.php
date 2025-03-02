<?php

namespace App\Http\Controllers;

use App\Services\DataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArtikelsController extends Controller
{
    public function __construct(
        private readonly DataService $dataService
    )
    {
    }

    public function show($orderBy = null, $direction = 'asc'): View
    {
        switch ($orderBy) {
            case 'leverancier':
                $orderBy = 'leveranciers.naam';
                break;
            case 'artikel':
                $orderBy = 'artikels.omschrijving';
                break;
            case 'voorraad':
                $orderBy = 'voorraad.vrij';
                break;
            case 'categorie':
                $orderBy = 'assortimentsgroep.omschrijving';
                break;
            case 'prijs':
                $orderBy = 'artikels.verkoopprijs';
                break;
            default:
                $orderBy = 'artikels.omschrijving';
        }
        $artikels = $this->dataService->getAllArtikels(50, $orderBy, $direction);

        return view('artikels', [
            'artikels' => $artikels ,
            'currentOrderBy' => $orderBy,
            'currentDirection' => $direction
        ]);
    }

    public function getArtikel($ean)
    {
        $data = $this->dataService->getArtikelByEan($ean);
        return view('artikel', ['data' => $data]);
    }
}
