<?php

namespace App\Filament\Project\Widgets;

use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
use App\Models\ProjectUser;
use Filament\Widgets\Widget;

class ProjectMembersWidget extends Widget
{
    protected string $view = 'filament.project.widgets.project-members-widget';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return ProjectMemberResource::canViewAny();
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $baseQuery = ProjectMemberResource::getEloquentQuery();

        $totalCount = (clone $baseQuery)->count();

        $rows = (clone $baseQuery)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (ProjectUser $membership): array => [
                'id' => $membership->id,
                'name' => $membership->user?->name ?? '',
                'avatar_url' => $membership->user?->avatarUrl(),
                'initials' => initials($membership->user?->name),
                'is_pending' => $membership->user?->email_verified_at === null,
                'role_label' => $membership->role?->displayLabel(),
                'is_admin_role' => $membership->role?->name === 'admin',
            ])
            ->all();

        $isEmpty = $rows === [];

        return [
            'heading' => __('dashboard.project.members_heading'),
            'subtitle' => $isEmpty
                ? __('dashboard.project.members_empty_subtitle')
                : __('dashboard.project.members_subtitle', ['count' => $totalCount]),
            'emptyMessage' => __('dashboard.project.members_empty_body'),
            'viewAllLabel' => __('dashboard.common.view_all'),
            'viewAllUrl' => ProjectMemberResource::canViewAny()
                ? ProjectMemberResource::getUrl()
                : null,
            'rows' => $rows,
            'isEmpty' => $isEmpty,
        ];
    }
}
