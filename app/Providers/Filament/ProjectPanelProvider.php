<?php

namespace App\Providers\Filament;

use App\Filament\Panels\ProjectPanel;
use App\Filament\Project\Widgets\FinanceOverviewStats;
use App\Filament\Project\Widgets\ProjectCalendar;
use App\Filament\Project\Widgets\ProjectInfoOverview;
use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\EnforceOrganizationEmailVerification;
use App\Http\Middleware\EnforceOrganizationTwoFactor;
use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Http\Middleware\RegisterSpotlightCommands;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Panel;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class ProjectPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'project';

    public function register(): void
    {
        Filament::registerPanel(
            fn (): Panel => $this->panel(ProjectPanel::make()),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $this->withTenantMenus(parent::panel($panel))
            ->id(self::PANEL_ID)
            ->path('{organization}')
            ->tenant(Project::class, slugAttribute: 'slug')
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
            ->widgets([
                ProjectInfoOverview::class,
                FinanceOverviewStats::class,
                ProjectCalendar::class,
            ])
            ->plugin(FilamentFullCalendarPlugin::make())
            ->authMiddleware([
                RedirectToOrganizationLogin::class,
            ])
            ->tenantMiddleware([
                ApplyTenantContext::class,
                EnforceOrganizationEmailVerification::class,
                EnforceOrganizationTwoFactor::class,
                RegisterSpotlightCommands::class,
            ], isPersistent: true);
    }
}
