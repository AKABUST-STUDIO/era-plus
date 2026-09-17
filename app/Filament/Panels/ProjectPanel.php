<?php

namespace App\Filament\Panels;

use App\Filament\Project\Pages\Overview;
use App\Models\Project;
use App\Providers\Filament\OrganizationPanelProvider;
use App\Providers\Filament\ProjectPanelProvider;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;

class ProjectPanel extends Panel
{
    public function getUrl(?Model $tenant = null): ?string
    {
        if ((! $tenant) && $this->hasTenancy() && $this->auth()->hasUser()) {
            $tenant = Filament::getUserDefaultTenant($this->auth()->user());
        }

        if ($tenant instanceof Project) {
            return Overview::getUrl(
                [
                    'organization' => $tenant->organization->slug,
                    'tenant' => $tenant,
                ],
                panel: ProjectPanelProvider::PANEL_ID,
                tenant: $tenant,
            );
        }

        return Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getUrl();
    }
}
