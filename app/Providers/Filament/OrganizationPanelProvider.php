<?php

namespace App\Providers\Filament;

use App\Filament\Organization\Pages\Tenancy\RegisterOrganization;
use App\Filament\Organization\Settings\Pages\GeneralSettings;
use App\Http\Middleware\ApplyTenantContext;
use App\Http\Middleware\EnforceOrganizationEmailVerification;
use App\Http\Middleware\EnforceOrganizationTwoFactor;
use App\Models\Organization;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;

class OrganizationPanelProvider extends BasePanelProvider
{
    public const PANEL_ID = 'organization';

    public function panel(Panel $panel): Panel
    {
        return $this->withTenantMenus(parent::panel($panel))
            ->default()
            ->id(self::PANEL_ID)
            ->path('')
            ->login()
            ->registration()
            ->tenant(Organization::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterOrganization::class)
            ->discoverResources(
                in: app_path('Filament/Organization/Resources'),
                for: 'App\\Filament\\Organization\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Organization/Pages'),
                for: 'App\\Filament\\Organization\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/Organization/Widgets'),
                for: 'App\\Filament\\Organization\\Widgets',
            )
            ->pages([
                Dashboard::class,
            ])
            ->navigationItems($this->getNavigationItems())
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenantMiddleware([
                ApplyTenantContext::class,
                EnforceOrganizationEmailVerification::class,
                EnforceOrganizationTwoFactor::class,
            ], isPersistent: true);
    }

    /**
     * @return array<int, NavigationItem>
     */
    protected function getNavigationItems(): array
    {
        return [
            NavigationItem::make('Settings')
                ->icon('lucide-settings')
                ->sort(99)
                ->url(fn (): string => GeneralSettings::getUrl(
                    panel: SettingsPanelProvider::PANEL_ID,
                )),
        ];
    }
}
