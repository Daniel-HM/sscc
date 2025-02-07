<?php

namespace App\Http\Controllers;

use App\Services\DataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

            if (preg_match('/^\d{18}$/', $validatedBarcode)) {
                $data = $this->dataService->getArtikelsBySscc($validatedBarcode);
                $type = 'sscc';
            } elseif (preg_match('/^\d{13}$/', $validatedBarcode)) {
                $data = collect($this->dataService->getArtikelByEan($validatedBarcode));
                $type = 'ean';
            } else {
                $data = null;
            }

            return match (true) {
                $data->isEmpty() => $this->handleEmptyResult($validatedBarcode),
                default => view('scanned', [
                    'data' => $data,
                    'barcode' => $validatedBarcode,
                    'type' => $type
                ])
            };

        } catch (ValidationException $e) {
            Log::warning('Invalid barcode request', [
                'errors' => $e->errors(),
                'input' => $request->input('barcode'),
            ]);

            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Barcode processing error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'An unexpected error occurred');

        }
    }

    private function validateBarcode(Request $request): string
    {
        return $request->validate([
            'barcode-input' => 'required|digits_between:13,18|numeric',
        ])['barcode-input'];
    }

    private function handleEmptyResult(string $barcode)
    {
        Log::info('No data found for barcode', ['barcode' => $barcode]);

        return back()->with('warning', 'No information found for this barcode');
    }


}
