<?php

namespace App\Filament\Panels;

use Filament\Facades\Filament;
use Filament\Panel;
use LivewireUI\Spotlight\Spotlight;
use pxlrbt\FilamentSpotlight\SpotlightPlugin as BaseSpotlightPlugin;

class SpotlightPlugin extends BaseSpotlightPlugin
{
    public function boot(Panel $panel): void
    {
        Filament::serving(function (): void {
            config()->set('livewire-ui-spotlight.include_js', false);
        });
    }

    public static function registerNavigation($panel): void
    {
        Spotlight::$commands = [];

        parent::registerNavigation($panel);
    }
}
