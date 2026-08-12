<?php

namespace App\Providers\Filament;

use App\Filament\Panels\ProjectPanel;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\EnforceOrganizationEmailVerification;
use App\Http\Middleware\EnforceOrganizationTwoFactor;
use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\RedirectToOrganizationLogin;
use App\Http\Middleware\RegisterSpotlightCommands;
use App\Models\Project;
use Filament\Facades\Filament;
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
        EnforceOrganizationEmailVerification::class,
        EnforceOrganizationTwoFactor::class,
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
