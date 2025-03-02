<?php

namespace App\Http\Controllers;

use App\Services\DataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BarcodeController extends Controller
{

    public function __construct(
        private readonly DataService $dataService
    )
    {
    }

    public function processBarcode(Request $request)
    {

        try {
            $validatedBarcode = $this->validateBarcode($request);

            Log::info('Barcode request validated', [
                'barcode' => $validatedBarcode,
            ]);
            // SSCC or EAN13?
            Log::debug('Validating barcode', ['barcode' => $validatedBarcode]);

            if (preg_match('/^SPS-\d{8}$/', $validatedBarcode)) {
                // Handles: SPS-00138001
                Log::debug('Matched SPS pattern');
                $data = collect($this->dataService->getArtikelsByPakbon($validatedBarcode));
                $type = 'sps';
                $table = true;
            } elseif (preg_match('/^\d{18}$/', $validatedBarcode)) {
                // Handles: 187119048018038368
                Log::debug('Matched SSCC-18 pattern');
                $data = collect($this->dataService->getArtikelsBySscc($validatedBarcode));
                $type = 'sscc';
                $table = true;
            } elseif (preg_match('/^\(00\)\d{18}$/', $validatedBarcode)) {
                // Handles: (00)187119048018038368
                Log::debug('Matched SSCC with (00) prefix');
                $validatedBarcode = Str::replace('(00)', '', $validatedBarcode);
                $data = collect($this->dataService->getArtikelsBySscc($validatedBarcode));
                $type = 'sscc';
                $table = true;
            } elseif (preg_match('/^\d{13}$/', $validatedBarcode)) {
                // Handles: 8711904221867
                Log::debug('Matched EAN pattern');
                $data = collect($this->dataService->getArtikelByEan($validatedBarcode));
                $type = 'ean';
                $table = false;
            } else {
                Log::debug('No pattern matched');
                $data = collect();
                $type = null;
                $table = false;
            }

            return match (true) {
                $data->isEmpty() => $this->handleEmptyResult($validatedBarcode),
                $type === 'ean' => view('artikel', [
                    'data' => $data,
                    'barcode' => $validatedBarcode,
                    'type' => $type,
                ]),
                default => view('result', [
                    'data' => $data,
                    'barcode' => $validatedBarcode,
                    'type' => $type,
                    'table' => $table
                ])
            };

        } catch (ValidationException $e) {
            Log::warning('Ongeldige zoekactie.', [
                'errors' => $e->errors(),
                'input' => $request->input('barcode-input'),
            ]);

            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Barcode processing error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Onverwachte fout!');

        }
    }

    private function validateBarcode(Request $request): string
    {
        return $request->validate([
            'barcode-input' => 'required|between:12,22',
        ])['barcode-input'];
    }

    private function handleEmptyResult(string $barcode)
    {
        Log::info('No data found for barcode', ['barcode' => $barcode]);

        return back()->with('warning', 'Helaas niets gevonden.');
    }

    public function getValidBarcodes(): JsonResponse
    {
        // Combine both types of barcodes into a single collection
        $validCodes = collect([])
            ->concat(
                DB::table('artikels')
                    ->select('ean as code')
                    ->selectRaw("'ean' as type")
                    ->whereNotNull('ean')
                    ->get()
            )
            ->concat(
                DB::table('sscc')
                    ->select('sscc as code')
                    ->selectRaw("'sscc' as type")
                    ->whereNotNull('sscc')
                    ->distinct('sscc')
                    ->get()
            );

        return response()->json($validCodes);
    }


}
