<?php

namespace App\Http\Controllers;

use App\Services\DataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;

class UploadController extends Controller
{
    protected $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function show()
    {

        return view('upload')->with([
            'success' => session('success'),
            'failed' => session('failed')
        ]);
    }

    public function upload(Request $request)
    {
        Log::info('Upload request received', [
            'hasFile' => $request->hasFile('xlsx'),
            'files' => $request->allFiles(),
            'headers' => $request->headers->all()
        ]);

        if (!$request->hasFile('xlsx')) {
            Log::warning('No file uploaded');
            return response()->json([
                'message' => 'Geen bestand geselecteerd'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'xlsx' => ['required', 'file', 'mimes:xlsx']
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $file = $request->file('xlsx');
            Log::info('File details', [
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]);

            Storage::disk('local')->putFileAs('Uploads', $file, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'-'.now()->format('Y-m-d').'.'.$file->extension());
            return response()->json([
                'message' => 'Upload voltooid'
            ]);
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Er is een fout opgetreden bij het uploaden'
            ], 500);
        }
    }

}
