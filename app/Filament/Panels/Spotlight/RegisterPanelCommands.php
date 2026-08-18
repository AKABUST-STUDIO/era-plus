<?php

namespace App\Filament\Panels\Spotlight;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Illuminate\Database\Eloquent\Model;
use LivewireUI\Spotlight\Spotlight;

class RegisterPanelCommands
{
    public static function boot(Panel $panel, ?Model $tenant = null, bool $includeUserMenu = false): void
    {
        PanelContext::run($panel, $tenant, function () use ($panel, $tenant, $includeUserMenu): void {
            self::registerPages($panel);

            if ($includeUserMenu) {
                self::registerUserMenu($panel);
            }

            self::registerResources($panel, $tenant);
        });
    }

    private static function registerPages(Panel $panel): void
    {
        foreach ($panel->getPages() as $pageClass) {
            /** @var Page $page */
            $page = new $pageClass;

            if (self::slugHasParameters($page::getSlug())) {
                continue;
            }

            if (method_exists($page, 'shouldRegisterSpotlight') && $page::shouldRegisterSpotlight() === false) {
                continue;
            }

            $name = collect([
                $page->getNavigationGroup(),
                $page->getTitle(),
            ])->filter()->join(' / ');

            $url = $page::getUrl();

            if (blank($name) || blank($url)) {
                continue;
            }

            $command = new PageCommand(
                name: PanelBreadcrumb::prefix($panel->getId(), $name),
                url: $url,
                panelId: $panel->getId(),
                kind: 'page',
            );

            Spotlight::$commands[$command->getId()] = $command;
        }
    }

    private static function registerUserMenu(Panel $panel): void
    {
        foreach ($panel->getUserMenuItems() as $key => $item) {
            $name = self::userMenuName($key, $item);
            $url = self::userMenuUrl($key, $item);

            if (blank($name) || blank($url)) {
                continue;
            }

            $command = new PageCommand(
                name: PanelBreadcrumb::prefix($panel->getId(), $name),
                url: $url,
                panelId: $panel->getId(),
                kind: 'action',
            );

            Spotlight::$commands[$command->getId()] = $command;
        }
    }

    private static function registerResources(Panel $panel, ?Model $tenant): void
    {
        foreach ($panel->getResources() as $resource) {
            if (method_exists($resource, 'shouldRegisterSpotlight') && $resource::shouldRegisterSpotlight() === false) {
                continue;
            }

            foreach ($resource::getPages() as $key => $page) {
                /** @var PageRegistration $page */
                if (blank($key) || blank($page->getPage())) {
                    continue;
                }

                $pageClass = $page->getPage();

                if (method_exists($pageClass, 'shouldRegisterSpotlight') && $pageClass::shouldRegisterSpotlight() === false) {
                    continue;
                }

                $command = new ResourceCommand(
                    resource: $resource,
                    page: $pageClass,
                    key: $key,
                    panelId: $panel->getId(),
                    tenant: $tenant,
                );

                Spotlight::$commands[$command->getId()] = $command;
            }
        }
    }

    private static function slugHasParameters(string $slug): bool
    {
        return preg_match('/{[^}]+}/', $slug) === 1;
    }

    private static function userMenuName(string $key, Action|MenuItem $item): ?string
    {
        $label = match ($key) {
            'profile', 'account' => __('user.menu.account'),
            'logout' => $item->getLabel() ?? __('user.menu.logout'),
            default => $item->getLabel(),
        };

        if ($label === null) {
            return null;
        }

        $plain = trim(strip_tags((string) $label));

        return $plain === '' ? null : $plain;
    }

    private static function userMenuUrl(string $key, Action|MenuItem $item): ?string
    {
        return match ($key) {
            'logout' => $item->getUrl() ?? Filament::getLogoutUrl(),
            default => $item->getUrl(),
        };
    }
}
