<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Http\Middleware\RegisterSpotlightCommands;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

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
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_BEFORE,
                fn (): View => view('filament.user.components.back'),
            )
            ->middleware([
                RegisterSpotlightCommands::class,
            ], isPersistent: true)
            ->authMiddleware([
                RedirectToOrganizationLogin::class,
            ]);
    }
}
