<?php

namespace App\Providers\Filament;

use App\Filament\Organization\Pages\Auth\Login;
use App\Filament\Organization\Pages\Auth\Register;
use App\Filament\Organization\Pages\Tenancy\CreateOrganization;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\EnforceOrganizationEmailVerification;
use App\Http\Middleware\EnforceOrganizationTwoFactor;
use App\Http\Middleware\RegisterSpotlightCommands;
use App\Models\Organization;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

class OrganizationPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'organization';

    protected const authMiddleware = [
        Authenticate::class,
    ];

    protected const tenantMiddleware = [
        ApplyTenantContext::class,
        EnforceOrganizationEmailVerification::class,
        EnforceOrganizationTwoFactor::class,
        RegisterSpotlightCommands::class,
    ];

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)

            ->default()
            ->id(self::PANEL_ID)
            ->path('')

            ->login(Login::class)
            ->registration(Register::class)
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): View => view('filament.auth.login-container-footer'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
                fn (): View => view('filament.auth.register-container-header'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
                fn (): View => view('filament.auth.register-container-footer'),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn (): View => view('filament.auth.topbar'),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_END,
                fn (): View => view('filament.auth.login-layout-footer'),
            )

            ->discoverResources(
                in: app_path('Filament/Organization/Resources'),
                for: 'App\\Filament\\Organization\\Resources',
            )
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Organization/Pages'),
                for: 'App\\Filament\\Organization\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/Organization/Widgets'),
                for: 'App\\Filament\\Organization\\Widgets',
            )

            ->tenantMenu(false)
            ->tenant(Organization::class, slugAttribute: 'slug')
            ->tenantRegistration(CreateOrganization::class)
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
                    ->icon('lucide-settings')
                    ->sort(99)
                    ->extraAttributes(['class' => 'border-t border-gray-950/10 pt-1 dark:border-white/10'])
                    ->visible(fn (): bool => OrganizationSettings::canAccess())
                    ->url(fn (): string => OrganizationSettings::getUrl(
                        panel: 'organization.settings',
                    )),
            ])

            ->authMiddleware(self::authMiddleware)
            ->tenantMiddleware(self::tenantMiddleware, isPersistent: true);
    }
}
