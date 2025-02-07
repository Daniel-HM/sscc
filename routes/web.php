<?php

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\PakbonController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/dashboard', [DashboardController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/check-mailbox', [MailboxController::class, 'checkMailbox']);


Route::get('/verwerk', [PakbonController::Class, 'findCsvFiles']);

Route::get('/scan-barcode', function () {
    return view('barcode');
})->middleware(['auth']);

Route::post('/scan-barcode', [BarcodeController::class, 'processBarcode'])
    ->middleware(['auth'])
    ->name('scan-barcode');

require __DIR__ . '/auth.php';
