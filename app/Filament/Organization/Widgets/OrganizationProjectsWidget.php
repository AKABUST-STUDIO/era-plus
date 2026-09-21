<?php

namespace App\Filament\Organization\Widgets;

use App\Facades\ProjectService;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class OrganizationProjectsWidget extends Widget
{
    protected string $view = 'filament.organization.widgets.organization-projects-widget';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return ProjectResource::canViewAny();
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $user = Filament::auth()->user();

        $projects = $user === null
            ? []
            : ProjectResource::getEloquentQuery()
                ->with(['participables'])
                ->orderBy('name')
                ->get()
                ->filter(fn (Project $project): bool => $user->can('view', $project))
                ->take(6)
                ->map(function (Project $project): array {
                    $participables = $project->participables;
                    $shown = $participables->take(5);

                    return [
                        'name' => $project->name,
                        'url' => ProjectService::urlFor($project),
                        'erasmus_action_label' => $project->erasmus_action?->getLabel(),
                        'erasmus_action_color' => $project->erasmus_action?->getColor(),
                        'participant_count' => $participables->count(),
                        'participants' => $shown->map(fn ($participation): array => [
                            'avatar_url' => $participation->avatarUrl(),
                            'initials' => initials($participation->participable?->name),
                            'name' => $participation->participable?->name ?? '',
                        ])->all(),
                        'extra' => max(0, $participables->count() - $shown->count()),
                    ];
                })
                ->values()
                ->all();

        $isEmpty = $projects === [];
        $canCreate = $user?->can('create', Project::class) ?? false;

        return [
            'heading' => __('dashboard.organization.projects_heading'),
            'subtitle' => $isEmpty
                ? __('dashboard.organization.projects_empty_subtitle')
                : trans_choice('dashboard.organization.projects_subtitle', count($projects), ['count' => count($projects)]),
            'emptyMessage' => __('dashboard.organization.projects_empty_body'),
            'noParticipantsLabel' => __('dashboard.organization.projects_no_participants'),
            'newProjectLabel' => __('dashboard.organization.projects_new'),
            'projects' => $projects,
            'isEmpty' => $isEmpty,
            'canCreate' => $canCreate,
            'createUrl' => $canCreate ? ProjectResource::getUrl('create') : null,
        ];
    }
}
