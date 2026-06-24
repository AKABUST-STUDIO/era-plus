<?php

namespace App\Providers\Filament;

use App\Enums\SubscriptionTier;
use App\Facades\OrganizationService;
use App\Models\Organization;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

abstract class BasePanelProvider extends PanelProvider
{
    /**
     * @var array<int, class-string>
     */
    protected const SHARED_MIDDLEWARE = [
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        DisableBladeIconComponents::class,
        DispatchServingFilamentEvent::class,
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->spa(hasPrefetching: true)

            ->domain('app.'.parse_url(config('app.url'), PHP_URL_HOST))
            ->globalSearch(position: GlobalSearchPosition::Sidebar)

            ->revealablePasswords()
            ->passwordReset(RequestPasswordReset::class)
            ->simpleProfilePage(true)

            ->brandName(config('app.name'))
            ->viteTheme('resources/css/filament/app/theme.css')
            // ->darkMode(false)
            ->colors([
                'primary' => Color::Zinc,
            ])
            ->topbar(false)
            ->sidebarFullyCollapsibleOnDesktop(true)

            ->userMenu(position: UserMenuPosition::Sidebar)
            ->userMenuItems($this->buildUserMenuItems())
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->middleware(static::SHARED_MIDDLEWARE);
    }

    /**
     * @return array<string, MenuItem>
     */
    protected function buildUserMenuItems(): array
    {
        $items = [
            'account' => MenuItem::make()
                ->label('Your account')
                ->icon('heroicon-o-user-circle')
                ->url(fn (): string => Filament::getPanel(\App\Providers\Filament\UserPanelProvider::PANEL_ID)?->getUrl() ?? '/me'),
        ];

        $items['upgrade'] = MenuItem::make()
            ->label('Upgrade to Pro')
            ->icon('heroicon-o-sparkles')
            ->visible(fn (): bool => $this->shouldShowUpgradeCta())
            ->url(fn (): ?string => $this->upgradeUrl());

        return $items;
    }

    protected function shouldShowUpgradeCta(): bool
    {
        $organization = $this->resolveOrganization();

        return $organization instanceof Organization
            && $organization->subscription_tier === SubscriptionTier::Free;
    }

    protected function upgradeUrl(): ?string
    {
        $organization = $this->resolveOrganization();

        if (! $organization instanceof Organization) {
            return null;
        }

        try {
            return Filament::getPanel(\App\Providers\Filament\Organization\SettingsPanelProvider::PANEL_ID)
                ?->getUrl(tenant: $organization);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveOrganization(): ?Organization
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Organization) {
            return $tenant;
        }

        return OrganizationService::current();
    }

    protected function withTenantMenus(Panel $panel): Panel
    {
        return $panel
            ->tenantMenu(false)
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): View => view('livewire.organization-menu-wrapper'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): View => view('livewire.project-menu-wrapper'),
            );
    }
}
