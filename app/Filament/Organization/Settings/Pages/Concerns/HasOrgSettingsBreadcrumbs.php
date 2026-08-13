<?php

namespace App\Filament\Organization\Settings\Pages\Concerns;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;

trait HasOrgSettingsBreadcrumbs
{
    public function getBreadcrumbs(): array
    {
        $organization = OrganizationService::current();

        return [
            Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getUrl(tenant: $organization) => $organization->name,
            OrganizationSettings::getUrl(panel: SettingsPanelProvider::PANEL_ID, tenant: $organization) => __('settings.breadcrumb'),
            $this->getTitle(),
        ];
    }
}
