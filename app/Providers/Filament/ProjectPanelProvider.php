<?php

namespace App\Providers\Filament;

use App\Filament\Panels\ProjectPanel;
use App\Filament\Project\Settings\Pages\ProjectSettings;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Http\Middleware\RegisterSpotlightCommands;
use App\Models\Project;
use App\Providers\Filament\Project\SettingsPanelProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\View\View;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class ProjectPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'project';

    protected const authMiddleware = [
        RedirectToOrganizationLogin::class,
        EnsureOrganizationAccess::class,
    ];

    protected const tenantMiddleware = [
        ApplyTenantContext::class,
        RegisterSpotlightCommands::class,
    ];

    public function register(): void
    {
        Filament::registerPanel(
            fn (): Panel => $this->panel(ProjectPanel::make()),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)

            ->id(self::PANEL_ID)
            ->path('{organization}')

            ->discoverResources(
                in: app_path('Filament/Project/Resources'),
                for: 'App\\Filament\\Project\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Project/Pages'),
                for: 'App\\Filament\\Project\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/Project/Widgets'),
                for: 'App\\Filament\\Project\\Widgets',
            )

            ->tenantMenu(false)
            ->tenant(Project::class, slugAttribute: 'slug')
            ->tenantRegistration(CreateProject::class)
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
                fn (): View => view('livewire.project-menu-wrapper'),
            )

            ->navigationItems([
                NavigationItem::make('Settings')
                    ->label(__('navigation.settings'))
                    ->icon('lucide-settings')
                    ->sort(99)
                    ->extraAttributes(['class' => 'border-t border-gray-950/10 pt-1 dark:border-white/10'])
                    ->visible(fn (): bool => ProjectSettings::canAccess())
                    ->url(fn (): string => ProjectSettings::getUrl(
                        panel: SettingsPanelProvider::PANEL_ID,
                    )),
            ])

            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable()
                    ->editable()
                    ->timezone('UTC')
                    ->locale(app()->getLocale()),
            ])

            ->authMiddleware(self::authMiddleware)
            ->tenantMiddleware(self::tenantMiddleware, isPersistent: true);
    }
}
