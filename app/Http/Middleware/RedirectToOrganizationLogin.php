<?php

namespace App\Http\Middleware;

use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

class RedirectToOrganizationLogin extends BaseAuthenticate
{
    protected function redirectTo(Request $request): ?string
    {
        return Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getLoginUrl();
    }
}
