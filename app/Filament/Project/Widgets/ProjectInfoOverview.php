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
            Stat::make('Project', $project->name)
                ->description($project->organization->name)
                ->color('primary'),
            Stat::make('Members', (string) $project->users()->count())
                ->description('Project members'),
            Stat::make('Created', $project->created_at?->toDateString() ?? '—')
                ->description('Project start'),
        ];
    }
}
