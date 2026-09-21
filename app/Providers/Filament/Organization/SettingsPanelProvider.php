<?php

namespace App\Providers\Filament\Organization;

use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Http\Middleware\RegisterSpotlightCommands;
use App\Providers\Filament\BasePanelProvider;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

class SettingsPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'organization.settings';

    protected const persistentMiddleware = [
        ApplyTenantContext::class,
        RegisterSpotlightCommands::class,
    ];

    protected const authMiddleware = [
        RedirectToOrganizationLogin::class,
        EnsureOrganizationAccess::class,
    ];

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)

            ->id(self::PANEL_ID)
            ->path('{organization}/settings')

            ->breadcrumbs()

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

            ->navigationItems([
                NavigationItem::make('back')
                    ->label(__('navigation.back'))
                    ->icon('lucide-arrow-left')
                    ->sort(-1)
                    ->extraAttributes(['class' => '[&_.fi-sidebar-item-label]:me-9 [&_.fi-sidebar-item-label]:text-center'])
                    ->url(filament()->getHomeUrl()),
            ])

            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): View => view('livewire.organization-menu-wrapper'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): View => view('livewire.spotlight-search'),
            )
            ->middleware(self::persistentMiddleware, isPersistent: true)
            ->authMiddleware(self::authMiddleware);
    }
}
