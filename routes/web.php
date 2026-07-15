<?php

use App\Http\Controllers\InvoiceDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/profile/invoices/{invoice}/download', InvoiceDownloadController::class)
    ->middleware('auth')
    ->name('invoices.download');
