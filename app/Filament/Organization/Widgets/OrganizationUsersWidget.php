<?php

namespace App\Filament\Organization\Widgets;

use App\Filament\Organization\Settings\Resources\OrganizationUsers\OrganizationUserResource;
use App\Models\Organization\OrganizationUser;
use Filament\Widgets\Widget;

class OrganizationUsersWidget extends Widget
{
    protected string $view = 'filament.organization.widgets.organization-users-widget';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return OrganizationUserResource::canViewAny();
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $baseQuery = OrganizationUserResource::getEloquentQuery();

        $totalCount = (clone $baseQuery)->count();

        $rows = (clone $baseQuery)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (OrganizationUser $membership): array => [
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
            'heading' => __('dashboard.organization.users_heading'),
            'subtitle' => $isEmpty
                ? __('dashboard.organization.users_empty_subtitle')
                : __('dashboard.organization.users_subtitle', ['count' => $totalCount]),
            'emptyMessage' => __('dashboard.organization.users_empty_body'),
            'viewAllLabel' => __('dashboard.common.view_all'),
            'viewAllUrl' => OrganizationUserResource::canViewAny()
                ? OrganizationUserResource::getUrl(panel: 'organization.settings')
                : null,
            'rows' => $rows,
            'isEmpty' => $isEmpty,
        ];
    }
}
