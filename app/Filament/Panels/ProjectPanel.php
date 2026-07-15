<?php

namespace App\Filament\Panels;

use App\Models\Project;
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
            return route($this->generateRouteName('pages.dashboard'), [
                'organization' => $tenant->organization->slug,
                'tenant' => $tenant,
            ]);
        }

        return parent::getUrl($tenant);
    }
}
