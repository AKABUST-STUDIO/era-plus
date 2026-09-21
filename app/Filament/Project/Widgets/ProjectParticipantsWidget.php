<?php

namespace App\Filament\Project\Widgets;

use App\Filament\Project\Resources\ProjectParticipants\ProjectParticipantResource;
use App\Models\Project\ProjectParticipant;
use Filament\Widgets\Widget;

class ProjectParticipantsWidget extends Widget
{
    protected string $view = 'filament.project.widgets.project-participants-widget';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return ProjectParticipantResource::canViewAny();
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $baseQuery = ProjectParticipantResource::getEloquentQuery();

        $totalCount = (clone $baseQuery)->count();

        $rows = (clone $baseQuery)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (ProjectParticipant $participation): array => [
                'id' => $participation->id,
                'name' => $participation->participable?->name ?? '—',
                'avatar_url' => $participation->avatarUrl(),
                'initials' => initials($participation->participable?->name),
            ])
            ->all();

        $isEmpty = $rows === [];

        return [
            'heading' => __('dashboard.project.participants_heading'),
            'subtitle' => $isEmpty
                ? __('dashboard.project.participants_empty_subtitle')
                : trans_choice('dashboard.project.participants_subtitle', $totalCount, ['count' => $totalCount]),
            'emptyMessage' => __('dashboard.project.participants_empty_body'),
            'viewAllLabel' => __('dashboard.common.view_all'),
            'viewAllUrl' => ProjectParticipantResource::canViewAny()
                ? ProjectParticipantResource::getUrl()
                : null,
            'rows' => $rows,
            'isEmpty' => $isEmpty,
        ];
    }
}
