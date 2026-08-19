<?php

namespace App\Filament\Project\Settings\Pages\Concerns;

use App\Facades\ProjectService;
use App\Filament\Project\Settings\Pages\ProjectSettings;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;

trait HasProjectSettingsBreadcrumbs
{
    /**
     * @return array<int|string, string|null>
     */
    public function getBreadcrumbs(): array
    {
        $project = ProjectService::current();

        if (! $project instanceof Project) {
            return [$this->getTitle()];
        }

        $breadcrumbs = [
            ProjectService::urlFor($project) => $project->name,
            ProjectSettings::getUrl(['project' => $project->slug]) => __('settings.breadcrumb'),
        ];

        if ($this instanceof EditRecord || $this instanceof ViewRecord || $this instanceof CreateRecord) {
            $resource = static::getResource();
            $breadcrumbs[$this->getResourceUrl()] = $resource::getBreadcrumb();
        }

        $breadcrumbs[] = $this->getTitle();

        return $breadcrumbs;
    }
}
