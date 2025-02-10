<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;


class PdfController extends Controller
{
    public function __construct(
        private readonly Parser $parser
    )
    {
    }

    public function getDateFromFirstPageOfPdf(string $directory, string $file): ?string
    {
        Log::info('Retrieving date from PDF file: ' . $file);
        // Path to the PDF file
        $pdfPath = storage_path("app/private/{$directory}/{$file}.pdf");
        if (!str_ends_with($pdfPath, '.pdf')) {
            return null;
        }

        try {
            $pdf = $this->parser->parseFile($pdfPath);
            $firstPageText = $pdf->getPages()[0]->getText();

            if (preg_match('/(\d{2}-\d{2}-\d{4})\S*/', $firstPageText, $matches)) {
                return $matches[1];
            }
        } catch (Exception $e) {
            Log::error("Failed to parse PDF: {$e->getMessage()}", [
                'file' => $file,
                'directory' => $directory
            ]);
        }

        return null;
    }
}
