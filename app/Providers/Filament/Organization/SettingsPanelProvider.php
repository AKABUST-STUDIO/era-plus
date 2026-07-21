<?php

namespace App\Providers\Filament\Organization;

use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\EnforceOrganizationEmailVerification;
use App\Http\Middleware\EnforceOrganizationTwoFactor;
use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Http\Middleware\RegisterSpotlightCommands;
use App\Providers\Filament\BasePanelProvider;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

class SettingsPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'organization.settings';

    public function panel(Panel $panel): Panel
    {
        return $this->withTenantMenus(parent::panel($panel))
            ->id(self::PANEL_ID)
            ->path('{organization}/settings')
            ->discoverResources(
                in: app_path('Filament/Organization/Settings/Resources'),
                for: 'App\\Filament\\Organization\\Settings\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Organization/Settings/Pages'),
                for: 'App\\Filament\\Organization\\Settings\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/Organization/Settings/Widgets'),
                for: 'App\\Filament\\Organization\\Settings\\Widgets',
            )
            ->middleware([
                ApplyTenantContext::class,
                EnforceOrganizationEmailVerification::class,
                EnforceOrganizationTwoFactor::class,
                RegisterSpotlightCommands::class,
            ], isPersistent: true)
            ->authMiddleware([
                RedirectToOrganizationLogin::class,
            ]);
    }
}
