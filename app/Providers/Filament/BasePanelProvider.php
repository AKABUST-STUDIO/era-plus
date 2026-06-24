<?php

namespace App\Providers\Filament;

use App\Facades\OrganizationService;
use App\Filament\User\Pages\Settings;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconPosition;
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

            ->brandName(config('app.name'))
            ->viteTheme('resources/css/filament/app/theme.css')

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
     * @return array<string, Action>
     */
    protected function buildUserMenuItems(): array
    {
        return [
            'account' => Action::make('account')
                ->label('Your account')
                ->icon('lucide-user')
                ->iconPosition(IconPosition::Before)
                ->url(fn (): string => Settings::getUrl(panel: UserPanelProvider::PANEL_ID)),
            'upgrade' => Action::make('upgrade')
                ->label('Upgrade to Pro')
                ->icon('lucide-sparkles')
                ->iconPosition(IconPosition::After)
                ->visible(fn (): bool => $this->shouldShowUpgradeCta())
                ->url(fn (): ?string => $this->upgradeUrl()),
        ];
    }

    protected function shouldShowUpgradeCta(): bool
    {
        return OrganizationService::shouldShowUpgradeCta();
    }

    protected function upgradeUrl(): ?string
    {
        $organization = $this->resolveOrganization();

        if (! $organization instanceof Organization) {
            return null;
        }

        try {
            return route('filament.organization-settings.resources.billing.index', ['tenant' => $organization]);
        } catch (\Throwable) {
            try {
                return route('filament.organization-settings.pages.billing', ['tenant' => $organization]);
            } catch (\Throwable) {
                return null;
            }
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
