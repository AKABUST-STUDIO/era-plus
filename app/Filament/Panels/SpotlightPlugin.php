<?php

namespace App\Filament\Panels;

use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Filament\Panels\Spotlight\RegisterPanelCommands;
use App\Providers\Filament\Organization\SettingsPanelProvider as OrganizationSettingsPanelProvider;
use App\Providers\Filament\OrganizationPanelProvider;
use App\Providers\Filament\Project\SettingsPanelProvider as ProjectSettingsPanelProvider;
use App\Providers\Filament\ProjectPanelProvider;
use App\Providers\Filament\UserPanelProvider;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
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

        foreach (self::accessiblePanels() as [$targetPanel, $tenant]) {
            RegisterPanelCommands::boot(
                $targetPanel,
                $tenant,
                includeUserMenu: $targetPanel->getId() === UserPanelProvider::PANEL_ID,
            );
        }
    }

    /**
     * @return list<array{Panel, ?Model}>
     */
    private static function accessiblePanels(): array
    {
        $panels = [
            [Filament::getPanel(UserPanelProvider::PANEL_ID), null],
        ];

        $organization = OrganizationService::current();

        if ($organization) {
            $panels[] = [Filament::getPanel(OrganizationPanelProvider::PANEL_ID), $organization];
            $panels[] = [Filament::getPanel(OrganizationSettingsPanelProvider::PANEL_ID), $organization];
        }

        $project = ProjectService::current();

        if ($project) {
            $panels[] = [Filament::getPanel(ProjectPanelProvider::PANEL_ID), $project];
            $panels[] = [Filament::getPanel(ProjectSettingsPanelProvider::PANEL_ID), $project];
        }

        return $panels;
    }
}
