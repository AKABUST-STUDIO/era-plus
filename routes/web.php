<?php

use App\Http\Controllers\InvoiceDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/profile/invoices/{invoice}/download', InvoiceDownloadController::class)
    ->middleware('auth')
    ->name('invoices.download');

Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');
