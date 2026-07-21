<?php

namespace App\Http\Middleware;

use App\Filament\Panels\SpotlightPlugin;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegisterSpotlightCommands
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Filament::auth()->check()) {
            SpotlightPlugin::registerNavigation(Filament::getCurrentPanel());
        }

        return $next($request);
    }
}
