<?php

namespace App\Filament\Panels;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class PanelPage
{
    public static function currentSlug(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId();
        $routeName = request()->route()?->getName();

        if ($panelId === null || $routeName === null) {
            return '';
        }

        $prefix = "filament.{$panelId}.";

        if (! str_starts_with($routeName, $prefix)) {
            return '';
        }

        $name = substr($routeName, strlen($prefix));

        return match (true) {
            str_starts_with($name, 'pages.') => substr($name, strlen('pages.')),
            str_starts_with($name, 'resources.') && str_ends_with($name, '.index') => substr($name, strlen('resources.'), -strlen('.index')),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function url(string $panelId, string $slug, Model $tenant, array $parameters = []): ?string
    {
        if ($slug === '') {
            return null;
        }

        $panel = Filament::getPanel($panelId);

        foreach ($panel->getPages() as $page) {
            if ($page::getSlug($panel) === $slug) {
                return $page::getUrl($parameters, panel: $panelId, tenant: $tenant);
            }
        }

        foreach ($panel->getResources() as $resource) {
            if ($resource::getSlug($panel) === $slug) {
                return $resource::getUrl('index', $parameters, panel: $panelId, tenant: $tenant);
            }
        }

        return null;
    }
}
