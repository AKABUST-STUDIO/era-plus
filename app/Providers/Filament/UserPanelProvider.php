<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectToOrganizationLogin;
use Filament\Pages\Dashboard;
use Filament\Panel;

class UserPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'user';

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)
            ->id(self::PANEL_ID)
            ->path('profile')
            ->discoverResources(
                in: app_path('Filament/User/Resources'),
                for: 'App\\Filament\\User\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/User/Pages'),
                for: 'App\\Filament\\User\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/User/Widgets'),
                for: 'App\\Filament\\User\\Widgets',
            )
            ->pages([
                Dashboard::class,
            ])
            ->authMiddleware([
                RedirectToOrganizationLogin::class,
            ]);
    }
}
