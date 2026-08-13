<?php

namespace App\Filament\Project\Settings\Pages\Concerns;

use App\Facades\ProjectService;
use App\Filament\Project\Settings\Pages\ProjectSettings;
use App\Models\Project;

trait HasProjectSettingsBreadcrumbs
{
    /**
     * @return array<string, string>|array<int|string, string>
     */
    public function getBreadcrumbs(): array
    {
        $project = ProjectService::current();

        if (! $project instanceof Project) {
            return [$this->getTitle()];
        }

        return [
            ProjectService::urlFor($project) => $project->name,
            ProjectSettings::getUrl(['project' => $project->slug]) => __('settings.breadcrumb'),
            $this->getTitle(),
        ];
    }
}
