<?php

namespace App\Providers\Filament\Project;

use App\Facades\ProjectService;
use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\EnsureProjectAccess;
use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Http\Middleware\RegisterSpotlightCommands;
use App\Models\Project;
use App\Providers\Filament\BasePanelProvider;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

class SettingsPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'project.settings';

    protected const persistentMiddleware = [
        ApplyTenantContext::class,
        RegisterSpotlightCommands::class,
    ];

    protected const authMiddleware = [
        RedirectToOrganizationLogin::class,
        EnsureOrganizationAccess::class,
        EnsureProjectAccess::class,
    ];

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)

            ->id(self::PANEL_ID)
            ->path('{organization}/{project}/settings')

            ->breadcrumbs()

            ->discoverResources(
                in: app_path('Filament/Project/Settings/Resources'),
                for: 'App\\Filament\\Project\\Settings\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Project/Settings/Pages'),
                for: 'App\\Filament\\Project\\Settings\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/Project/Settings/Widgets'),
                for: 'App\\Filament\\Project\\Settings\\Widgets',
            )

            ->navigationItems([
                NavigationItem::make('back')
                    ->label(__('navigation.back'))
                    ->icon('lucide-arrow-left')
                    ->sort(-1)
                    ->extraAttributes(['class' => '[&_.fi-sidebar-item-label]:me-9 [&_.fi-sidebar-item-label]:text-center'])
                    ->url(function (): ?string {
                        $project = ProjectService::current();

                        return $project instanceof Project ? ProjectService::urlFor($project) : null;
                    }),
            ])

            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): View => view('livewire.organization-menu-wrapper'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): View => view('livewire.spotlight-search'),
            )
            ->renderHook(
                PanelsRenderHook::CONTENT_BEFORE,
                fn (): View => view('livewire.sidebar-toggle-wrapper'),
            )

            ->middleware(self::persistentMiddleware, isPersistent: true)
            ->authMiddleware(self::authMiddleware);
    }
}
