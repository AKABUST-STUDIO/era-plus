<?php

namespace App\Filament\Project\Widgets;

use App\Filament\Project\Pages\Activity;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Presenters\ActivityLogPresenter;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class ProjectActivityWidget extends Widget
{
    protected string $view = 'filament.project.widgets.project-activity-widget';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return Filament::auth()->user()?->can('view', ActivityLog::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $project = Filament::getTenant();

        $entries = $project instanceof Project
            ? ActivityLog::query()
                ->where('project_id', $project->id)
                ->with(['causer', 'subject'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (ActivityLog $log): array => ActivityLogPresenter::present($log))
                ->all()
            : [];

        $isEmpty = $entries === [];

        return [
            'heading' => __('dashboard.project.activity_heading'),
            'subtitle' => $isEmpty
                ? __('dashboard.project.activity_empty_subtitle')
                : __('dashboard.project.activity_subtitle'),
            'emptyMessage' => __('dashboard.project.activity_empty_body'),
            'viewAllLabel' => __('dashboard.common.view_all'),
            'viewAllUrl' => Activity::canAccess() ? Activity::getUrl() : null,
            'entries' => $entries,
            'isEmpty' => $isEmpty,
        ];
    }
}
