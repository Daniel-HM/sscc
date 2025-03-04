<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataService;
use Illuminate\Support\Facades\Cache;

class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up the application cache with frequently used queries';

    public function __construct(
        private readonly DataService $dataService
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warmSpsCache();
        $this->warmSsccCache();
    }

    private function warmSpsCache()
    {
        $pakbonnen = $this->dataService->getAllPakbonnen();

        foreach ($pakbonnen as $pakbon) {
            Cache::put($pakbon->naam, $this->dataService->getArtikelsByPakbon($pakbon->naam), now()->addHours(48));

        }
    }

    private function warmSsccCache()
    {
        $ssccs = $this->dataService->getAllSscc();

        foreach ($ssccs as $sscc) {
            Cache::put($sscc->sscc, $this->dataService->getArtikelsBySscc($sscc->sscc), now()->addHours(48));
        }
    }

    private function warmArtikelsCache() {

    }
}
