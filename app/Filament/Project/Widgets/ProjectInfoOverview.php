<?php

namespace App\Filament\Project\Widgets;

use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectInfoOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected function getStats(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        return [
            Stat::make(__('forms.project.overview.project'), $project->name)
                ->description($project->organization->name)
                ->color('primary'),
            Stat::make(__('forms.project.overview.members'), (string) $project->users()->count())
                ->description(__('forms.project.overview.project_members')),
            Stat::make(__('forms.project.overview.created'), $project->created_at?->toDateString() ?? '—')
                ->description(__('forms.project.overview.project_start')),
        ];
    }
}
