<?php

namespace App\Http\Middleware;

use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Models\Organization;
use App\Models\Project;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Project) {
            URL::defaults(['organization' => $tenant->organization->slug]);
            ProjectService::remember($tenant);
            OrganizationService::remember($tenant->organization);
        } elseif ($tenant instanceof Organization) {
            URL::defaults(['organization' => $tenant->slug]);
            OrganizationService::remember($tenant);
            ProjectService::forget();
        } elseif (($organization = $request->route('organization')) instanceof Organization) {
            URL::defaults(['organization' => $organization->slug]);
            OrganizationService::remember($organization);
        }

        return $next($request);
    }
}
