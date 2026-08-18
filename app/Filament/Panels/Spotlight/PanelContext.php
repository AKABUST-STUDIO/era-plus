<?php

namespace App\Filament\Panels\Spotlight;

use App\Models\Organization;
use App\Models\Project;
use Closure;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class PanelContext
{
    public static function run(Panel|string $panel, ?Model $tenant, Closure $callback): mixed
    {
        $panel = is_string($panel) ? Filament::getPanel($panel) : $panel;

        $previousPanel = Filament::getCurrentPanel();
        $previousTenant = Filament::getTenant();
        $previousDefaults = URL::getDefaultParameters();

        try {
            Filament::setCurrentPanel($panel);
            Filament::setTenant($tenant, isQuiet: true);
            URL::defaults(self::routeDefaults($tenant, $previousDefaults));

            return $callback();
        } finally {
            Filament::setCurrentPanel($previousPanel);
            Filament::setTenant($previousTenant, isQuiet: true);
            URL::defaults($previousDefaults);
        }
    }

    /**
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    public static function routeDefaults(?Model $tenant, array $previous = []): array
    {
        if ($tenant instanceof Project) {
            return [...$previous, 'organization' => $tenant->organization->slug, 'project' => $tenant->slug];
        }

        if ($tenant instanceof Organization) {
            $previous['organization'] = $tenant->slug;
            unset($previous['project']);

            return $previous;
        }

        return $previous;
    }
}
