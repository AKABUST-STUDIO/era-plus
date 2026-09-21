<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Http\Middleware\RegisterSpotlightCommands;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

class UserPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'user';

    protected const persistentMiddleware = [
        RegisterSpotlightCommands::class,
    ];

    protected const authMiddleware = [
        RedirectToOrganizationLogin::class,
    ];

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

            ->navigationItems([
                NavigationItem::make('back')
                    ->label(__('navigation.back'))
                    ->icon('lucide-arrow-left')
                    ->sort(-1)
                    ->extraAttributes(['class' => '[&_.fi-sidebar-item-label]:me-9 [&_.fi-sidebar-item-label]:text-center'])
                    ->url(fn (): ?string => Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getUrl()),
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): View => view('livewire.spotlight-search'),
            )
            ->middleware(self::persistentMiddleware, isPersistent: true)
            ->authMiddleware(self::authMiddleware);
    }
}
