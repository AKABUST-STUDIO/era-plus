<?php

namespace App\Filament\Organization\Widgets;

use App\Filament\Organization\Pages\Activity;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Presenters\ActivityLogPresenter;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class OrganizationActivityWidget extends Widget
{
    protected string $view = 'filament.organization.widgets.organization-activity-widget';

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
        $organization = Filament::getTenant();

        $entries = $organization instanceof Organization
            ? ActivityLog::query()
                ->where('organization_id', $organization->id)
                ->with(['causer', 'subject'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (ActivityLog $log): array => ActivityLogPresenter::present($log))
                ->all()
            : [];

        $isEmpty = $entries === [];

        return [
            'heading' => __('dashboard.organization.activity_heading'),
            'subtitle' => $isEmpty
                ? __('dashboard.organization.activity_empty_subtitle')
                : __('dashboard.organization.activity_subtitle'),
            'emptyMessage' => __('dashboard.organization.activity_empty_body'),
            'viewAllLabel' => __('dashboard.common.view_all'),
            'viewAllUrl' => Activity::canAccess() ? Activity::getUrl() : null,
            'entries' => $entries,
            'isEmpty' => $isEmpty,
        ];
    }
}
