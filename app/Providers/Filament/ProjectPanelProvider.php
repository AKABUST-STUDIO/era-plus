<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Models\Project;
use Filament\Pages\Dashboard;
use Filament\Panel;

class ProjectPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'project';

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
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Project\Widgets\FinanceOverviewStats::class,
            ])
            ->authMiddleware([
                RedirectToOrganizationLogin::class,
            ])
            ->tenantMiddleware([
                ApplyTenantContext::class,
            ], isPersistent: true);
    }
}
