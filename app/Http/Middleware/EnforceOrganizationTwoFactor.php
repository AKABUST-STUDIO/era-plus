<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\Project;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceOrganizationTwoFactor
{
    public const ALLOWLISTED_ROUTE_NAMES = [
        'filament.organization-settings.pages.two-factor-required',
        'filament.organization.auth.logout',
        'filament.organization-settings.auth.logout',
        'filament.project.auth.logout',
        'filament.user.auth.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->resolveOrganization();
        $user = $request->user();

        if ($organization === null || $user === null) {
            return $next($request);
        }

        if (! $organization->enforce_two_factor) {
            return $next($request);
        }

        if ($user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();
        if (\in_array($routeName, self::ALLOWLISTED_ROUTE_NAMES, true)) {
            return $next($request);
        }

        return redirect()->to(route(
            'filament.organization-settings.pages.two-factor-required',
            ['tenant' => $organization->slug],
        ));
    }

    private function resolveOrganization(): ?Organization
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Organization) {
            return $tenant;
        }

        if ($tenant instanceof Project) {
            return $tenant->organization;
        }

        return \App\Facades\OrganizationService::current();
    }
}
