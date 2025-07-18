<?php

use App\Http\Controllers\ArtikelsController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\LeveranciersController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\PakbonController;
use App\Http\Controllers\UploadController;
use App\Http\Middleware\CustomPostSize;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/logout', [LogoutController::class, 'logout'])->name('logout');


Route::get('/dashboard', [DashboardController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');


Route::get('/search', function () {
    return view('search');
})->middleware(['auth']);

Route::post('/search', [BarcodeController::class, 'processBarcode'])
    ->middleware(['auth'])
    ->name('search');

Route::get('/leveranciers', [LeveranciersController::class, 'show'])->where('page', '[0-9]+')->name('leveranciers');

Route::controller(ArtikelsController::class)->group(function () {
    Route::get('/artikel/{ean}', 'getArtikel')->name('artikel')->middleware(['auth', 'verified']);
    Route::get('/artikels/{orderBy?}/{direction?}', 'show')->name('artikels')->where('page', '[0-9]+')->middleware(['auth', 'verified']);
});

Route::get('/upload', [UploadController::class, 'show'])->name('upload.show')->middleware(['auth', 'verified']);
Route::post('/upload', [UploadController::class, 'upload'])
    ->middleware(CustomPostSize::class)
    ->middleware(['auth', 'verified'])
    ->name('upload.store');

Route::controller(PakbonController::class)->group(function () {
    Route::get('/pakbonnen', 'list')->name('pakbonnen.list')->middleware(['auth', 'verified']);
    Route::get('/pakbonnen/{pakbon}', 'show')->name('pakbonnen.show')->middleware(['auth', 'verified']);
});
Route::get('/valid-barcodes', [BarcodeController::class, 'getValidBarcodes'])->middleware(['auth']);

Route::get('/excel', [ExcelController::class, 'importExcelFile'])->middleware(['auth']);


// Basic EAN13 barcode generation (PNG format - Excel compatible)
Route::get('/barcode/{code}', [BarcodeController::class, 'generateEan13'])
    ->where('code', '[0-9]{12,13}')
    ->name('barcode.ean13');

// EAN13 barcode with format specification (PNG or SVG)
Route::get('/barcode/{code}/{format}', [BarcodeController::class, 'generateEan13'])
    ->where(['code' => '[0-9]{12,13}', 'format' => '(png|svg)'])
    ->name('barcode.ean13.format');

// EAN13 barcode with custom dimensions (PNG by default for Excel compatibility)
Route::get('/barcode/{code}/{width}/{height}', [BarcodeController::class, 'generateCustomEan13'])
    ->where(['code' => '[0-9]{12,13}', 'width' => '[0-9]{1,2}', 'height' => '[0-9]{2,3}'])
    ->name('barcode.ean13.custom');

// EAN13 barcode with custom dimensions and format
Route::get('/barcode/{code}/{width}/{height}/{format}', [BarcodeController::class, 'generateCustomEan13'])
    ->where(['code' => '[0-9]{12,13}', 'width' => '[0-9]{1,2}', 'height' => '[0-9]{2,3}', 'format' => '(png|svg)'])
    ->name('barcode.ean13.custom.format');

require __DIR__ . '/auth.php';
