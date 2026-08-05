<?php

use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withEvents(discover: true)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getLoginUrl());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
