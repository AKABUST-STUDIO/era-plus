<?php

namespace App\Filament\Organization\Settings\Pages\Concerns;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\GeneralSettings;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;

trait HasOrgSettingsBreadcrumbs
{
    public function getBreadcrumbs(): array
    {
        $organization = OrganizationService::current();

        return [
            Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getUrl(tenant: $organization) => $organization->name,
            GeneralSettings::getUrl(tenant: $organization) => __('settings.breadcrumb'),
            $this->getTitle(),
        ];
    }
}
