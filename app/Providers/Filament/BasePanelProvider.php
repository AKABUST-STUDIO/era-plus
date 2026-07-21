<?php

namespace App\Providers\Filament;

use App\Filament\Panels\SpotlightPlugin;
use App\Filament\Panels\UserMenu;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
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

            ->revealablePasswords()
            ->passwordReset(RequestPasswordReset::class)
            ->globalSearch(provider: true, position: GlobalSearchPosition::Sidebar)

            ->brandName(config('app.name'))
            ->viteTheme('resources/css/filament/app/theme.css')

            ->colors([
                'primary' => Color::Zinc,
            ])
            ->topbar(false)
            ->sidebarFullyCollapsibleOnDesktop(true)

            ->userMenu(false)
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->userMenuItems(UserMenu::items())
            ->plugins([
                SpotlightPlugin::make(),
            ])
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
