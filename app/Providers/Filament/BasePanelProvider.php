<?php

namespace App\Providers\Filament;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Enums\GlobalSearchPosition;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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

            ->brandName(config('app.name'))
            ->viteTheme('resources/css/filament/app/theme.css')

            ->colors([
                'primary' => Color::Zinc,
            ])
            ->topbar(false)
            ->sidebarFullyCollapsibleOnDesktop(true)

            ->userMenu(false)
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn (): View => view('livewire.sidebar-brand-wrapper'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): View => view('livewire.user-footer-wrapper'),
            )
            ->middleware(static::SHARED_MIDDLEWARE);
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
