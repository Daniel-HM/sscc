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
    Route::get('/artikel/{ean}', 'getArtikel')->name('artikel');
    Route::get('/artikels/{orderBy?}/{direction?}', 'show')->name('artikels')->where('page', '[0-9]+');
});

Route::get('/upload', [UploadController::class, 'show'])->name('upload.show');
Route::post('/upload', [UploadController::class, 'upload'])->name('upload.store');

Route::controller(PakbonController::class)->group(function () {
    Route::get('/pakbonnen', 'list')->name('pakbonnen.list');
    Route::get('/pakbonnen/{pakbon}', 'show')->name('pakbonnen.show');
});
Route::get('/valid-barcodes', [BarcodeController::class, 'getValidBarcodes'])->middleware(['auth']);

Route::get('/excel', [ExcelController::class, 'importExcelFile'])->middleware(['auth']);

require __DIR__ . '/auth.php';
