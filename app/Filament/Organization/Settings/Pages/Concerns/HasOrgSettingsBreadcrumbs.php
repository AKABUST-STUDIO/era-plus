<?php

namespace App\Filament\Organization\Settings\Pages\Concerns;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Models\Organization;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;

trait HasOrgSettingsBreadcrumbs
{
    /**
     * @return array<int|string, string|null>
     */
    public function getBreadcrumbs(): array
    {
        $organization = OrganizationService::current();

        if (! $organization instanceof Organization) {
            return [$this->getTitle()];
        }

        $breadcrumbs = [
            Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getUrl(tenant: $organization) => $organization->name,
            OrganizationSettings::getUrl(panel: SettingsPanelProvider::PANEL_ID, tenant: $organization) => __('settings.breadcrumb'),
        ];

        if ($this instanceof EditRecord || $this instanceof ViewRecord || $this instanceof CreateRecord) {
            $resource = static::getResource();
            $breadcrumbs[$this->getResourceUrl()] = $resource::getBreadcrumb();
        }

        $breadcrumbs[] = $this->getTitle();

        return $breadcrumbs;
    }
}
