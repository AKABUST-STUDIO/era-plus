<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\InvoiceDownloadController;
use Illuminate\Support\Facades\Route;
use Laragear\WebAuthn\Http\Routes as WebAuthnRoutes;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/profile/invoices/{invoice}/download', InvoiceDownloadController::class)
    ->middleware('auth')
    ->name('invoices.download');

Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');

Route::get('/auth/magic-link', MagicLinkController::class)->name('auth.magic-link');

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->whereIn('provider', ['google', 'microsoft', 'apple'])
    ->name('auth.oauth.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->whereIn('provider', ['google', 'microsoft', 'apple'])
    ->name('auth.oauth.callback');

WebAuthnRoutes::register();
