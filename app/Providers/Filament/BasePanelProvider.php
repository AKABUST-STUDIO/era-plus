<?php

namespace App\Providers\Filament;

use App\Filament\Panels\SpotlightPlugin;
use App\Filament\Panels\UserMenu;
use App\Http\Middleware\SetUserLocale;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Contracts\Plugin;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

abstract class BasePanelProvider extends PanelProvider
{
    protected const colors = [
        'primary' => Color::Zinc,
        'accent' => Color::Sky,
    ];

    protected const plugins = [
        SpotlightPlugin::class,
    ];

    protected const middleware = [
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
        SubstituteBindings::class,
        DisableBladeIconComponents::class,
        DispatchServingFilamentEvent::class,
        SetUserLocale::class,
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->spa(hasPrefetching: true)
            ->domain('app.'.parse_url(config('app.url'), PHP_URL_HOST))

            ->revealablePasswords()
            ->passwordReset(RequestPasswordReset::class)

            ->breadcrumbs(false)

            ->userMenu(false)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->userMenuItems(UserMenu::items())
            ->topbar(false)
            ->sidebarFullyCollapsibleOnDesktop(true)
            ->globalSearch(false)
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->databaseNotificationsPolling('5s')

            ->brandName(config('app.name'))
            ->viteTheme('resources/css/filament/app/theme.css')

            ->colors(static::colors)
            ->plugins(array_map(fn (string $plugin): Plugin => $plugin::make(), static::plugins))
            ->middleware(static::middleware);
    }
}
