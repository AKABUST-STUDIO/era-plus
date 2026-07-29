<?php

namespace App\Filament\Organization\Settings\Pages\Concerns;

use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;

trait HasOrgSettingsBreadcrumbs
{
    public function getBreadcrumbs(): array
    {
        $organization = $this->breadcrumbOrganization();

        $segments = [];

        if ($organization instanceof Organization) {
            $orgUrl = Filament::getPanel(OrganizationPanelProvider::PANEL_ID)
                ?->getUrl(tenant: $organization);

            if ($orgUrl !== null) {
                $segments[$orgUrl] = $organization->name;
            } else {
                $segments[] = $organization->name;
            }
        }

        $segments[] = __('settings.breadcrumb');
        $segments[] = $this->getTitle();

        return $segments;
    }

    protected function breadcrumbOrganization(): ?Organization
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Organization) {
            return $tenant;
        }

        return OrganizationService::current();
    }
}
