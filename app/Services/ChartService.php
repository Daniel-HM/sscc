<?php


namespace App\Services;

use App\Models\Artikels;
use App\Models\Assortimentsgroep;
use App\Models\Leveranciers;
use App\Models\Pakbonnen;
use App\Models\Sscc;
use IcehouseVentures\LaravelChartjs\Facades\Chartjs;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChartService
{
    protected $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function top20ArtikelsPerAssortimentsgroepChart()
    {
        $artikelCountByAssortimentsgroep = $this->dataService->countArtikelsByAssortimentsgroep();
        $labels = $artikelCountByAssortimentsgroep->keys()->toArray();
        $counts = $artikelCountByAssortimentsgroep->values()->toArray();

        $colors = $this->generateColors(count($labels));

        // Extract background and border colors into separate arrays
        $backgroundColors = array_column($colors, 'background');
        $borderColors = array_column($colors, 'border');

        $chart = Chartjs::build()
            ->name('top20ArtikelsPerAssortimentsgroep')
            ->type('bar')
            ->size(['width' => 500, 'height' => 250])
            ->labels($labels)
            ->datasets([
                [
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'data' => $counts
                ],
            ])
            ->options([
                "scales" => [
                    "x" => [
                        "beginAtZero" => true
                    ]
                ],
                "plugins" => [
                    "legend" => [
                        "display" => false
                    ]],
                "indexAxis" => "y",
            ]);

        return $chart;
    }

    /**
     * Generate a visually pleasing array of distinct colors
     *
     * @param int $count Number of colors to generate
     * @return array Array of colors with background (rgba) and border (rgb) values
     */
    private function generateColors($count)
    {
        $colors = [];

        // Use golden ratio to create well-distributed hues
        $goldenRatioConjugate = 0.618033988749895;
        $hue = rand(0, 100) / 100; // Start with random hue

        for ($i = 0; $i < $count; $i++) {
            // Use golden ratio to get next hue
            $hue += $goldenRatioConjugate;
            $hue = fmod($hue, 1); // Keep within [0,1]

            // HSL to RGB conversion (simplified)
            $h = $hue * 360;
            $s = 0.7; // Fixed saturation
            $l = 0.5; // Fixed lightness

            // Convert HSL to RGB
            $c = (1 - abs(2 * $l - 1)) * $s;
            $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
            $m = $l - ($c / 2);

            if ($h < 60) {
                $r = $c;
                $g = $x;
                $b = 0;
            } else if ($h < 120) {
                $r = $x;
                $g = $c;
                $b = 0;
            } else if ($h < 180) {
                $r = 0;
                $g = $c;
                $b = $x;
            } else if ($h < 240) {
                $r = 0;
                $g = $x;
                $b = $c;
            } else if ($h < 300) {
                $r = $x;
                $g = 0;
                $b = $c;
            } else {
                $r = $c;
                $g = 0;
                $b = $x;
            }

            $r = round(($r + $m) * 255);
            $g = round(($g + $m) * 255);
            $b = round(($b + $m) * 255);

            $colors[] = [
                'background' => "rgba($r, $g, $b, 0.5)",
                'border' => "rgb($r, $g, $b)"
            ];
        }

        return $colors;
    }

}
