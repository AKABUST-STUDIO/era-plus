<?php

namespace App\Filament\Panels\Spotlight;

class PanelBreadcrumb
{
    public static function for(string $panelId): string
    {
        return __('menu.spotlight.panels.'.str_replace('.', '_', $panelId));
    }

    public static function prefix(string $panelId, string $name): string
    {
        $prefix = self::for($panelId);

        return trim($name) === '' ? $prefix : "{$prefix} / {$name}";
    }

    public static function order(string $panelId): int
    {
        return match ($panelId) {
            'user' => 0,
            'organization' => 1,
            'organization.settings' => 2,
            'project' => 3,
            'project.settings' => 4,
            default => 99,
        };
    }
}
