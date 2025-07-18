<?php

namespace App\Http\Controllers;

use App\Services\DataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Milon\Barcode\DNS1D;

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

    /**
     * Generate EAN13 barcode image
     *
     * @param string $code The EAN13 code (12 or 13 digits)
     * @param string $format Image format (png, svg) - default: png
     * @return Response
     */
    public function generateEan13($code, $format = 'png')
    {
        try {
            // Validate EAN13 format (12 or 13 digits)
            if (!$this->isValidEan13($code)) {
                return $this->errorResponse('Invalid EAN13 code. Must be 12 or 13 digits.', 400);
            }

            // Ensure we have exactly 13 digits (calculate check digit if needed)
            $code = $this->ensureEan13CheckDigit($code);

            // Validate format parameter
            $allowedFormats = ['png', 'svg'];
            if (!in_array(strtolower($format), $allowedFormats)) {
                return $this->errorResponse('Invalid format. Supported formats: png, svg', 400);
            }

            // Generate barcode based on format
            if (strtolower($format) === 'svg') {
                return $this->generateSvgBarcode($code);
            } else {
                return $this->generatePngBarcode($code);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Error generating barcode: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate PNG barcode
     */
    private function generatePngBarcode($code)
    {
        $barcode = DNS1D::getBarcodePNG($code, 'EAN13', 3, 50);

        return response($barcode)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=31536000') // Cache for 1 year
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }

    /**
     * Generate SVG barcode
     */
    private function generateSvgBarcode($code)
    {
        $barcode = DNS1D::getBarcodeSVG($code, 'EAN13', 3, 50);

        return response($barcode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=31536000') // Cache for 1 year
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }

    /**
     * Validate EAN13 code format
     */
    private function isValidEan13($code)
    {
        // Remove any non-numeric characters
        $code = preg_replace('/[^0-9]/', '', $code);

        // Must be 12 or 13 digits
        return preg_match('/^\d{12,13}$/', $code);
    }

    /**
     * Ensure EAN13 has proper check digit
     */
    private function ensureEan13CheckDigit($code)
    {
        // Remove any non-numeric characters
        $code = preg_replace('/[^0-9]/', '', $code);

        if (strlen($code) === 13) {
            // Validate the check digit
            if ($this->validateEan13CheckDigit($code)) {
                return $code;
            } else {
                // Recalculate check digit
                $code = substr($code, 0, 12);
            }
        }

        if (strlen($code) === 12) {
            // Calculate and append check digit
            return $code . $this->calculateEan13CheckDigit($code);
        }

        throw new \InvalidArgumentException('Invalid EAN13 code length');
    }

    /**
     * Calculate EAN13 check digit
     */
    private function calculateEan13CheckDigit($code)
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $code[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit;
    }

    /**
     * Validate EAN13 check digit
     */
    private function validateEan13CheckDigit($code)
    {
        if (strlen($code) !== 13) {
            return false;
        }

        $checkDigit = (int) substr($code, -1);
        $calculatedCheckDigit = $this->calculateEan13CheckDigit(substr($code, 0, 12));

        return $checkDigit === $calculatedCheckDigit;
    }

    /**
     * Return error response
     */
    private function errorResponse($message, $status = 400)
    {
        return response()->json([
            'error' => $message,
            'status' => $status
        ], $status);
    }

    /**
     * Generate barcode with custom dimensions (alternative endpoint)
     */
    public function generateCustomEan13($code, $width = 3, $height = 50, $format = 'png')
    {
        try {
            if (!$this->isValidEan13($code)) {
                return $this->errorResponse('Invalid EAN13 code. Must be 12 or 13 digits.', 400);
            }

            $code = $this->ensureEan13CheckDigit($code);

            // Validate dimensions
            $width = max(1, min(10, (int) $width));   // Between 1-10
            $height = max(20, min(200, (int) $height)); // Between 20-200

            if (strtolower($format) === 'svg') {
                $barcode = DNS1D::getBarcodeSVG($code, 'EAN13', $width, $height);
                return response($barcode)
                    ->header('Content-Type', 'image/svg+xml')
                    ->header('Cache-Control', 'public, max-age=31536000');
            } else {
                $barcode = DNS1D::getBarcodePNG($code, 'EAN13', $width, $height);
                return response($barcode)
                    ->header('Content-Type', 'image/png')
                    ->header('Cache-Control', 'public, max-age=31536000');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Error generating barcode: ' . $e->getMessage(), 500);
        }
    }

}
