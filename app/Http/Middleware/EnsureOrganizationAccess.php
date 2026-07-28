<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if ($organization instanceof Organization) {
            abort_unless(
                Filament::auth()->user()?->canAccessTenant($organization) ?? false,
                403,
            );
        }

        return $next($request);
    }
}
