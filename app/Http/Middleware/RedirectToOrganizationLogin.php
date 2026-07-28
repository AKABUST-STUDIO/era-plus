<?php

namespace App\Http\Middleware;

use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

class RedirectToOrganizationLogin extends FilamentAuthenticate
{
    protected function redirectTo($request): ?string
    {
        return Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getLoginUrl();
    }
}
