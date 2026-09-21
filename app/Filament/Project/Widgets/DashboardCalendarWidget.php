<?php

namespace App\Filament\Project\Widgets;

use App\Filament\Project\Resources\ProjectEvents\ProjectEventResource;
use App\Filament\Project\Resources\ProjectEvents\Widgets\ProjectEventsCalendar;
use Livewire\Attributes\On;

class DashboardCalendarWidget extends ProjectEventsCalendar
{
    protected string $view = 'filament.project.widgets.dashboard-calendar-widget';

    protected int|string|array $columnSpan = 1;

    public string $calendarTitle = '';

    public static function canView(): bool
    {
        return ProjectEventResource::canViewAny();
    }

    #[On('calendar-title-changed')]
    public function setCalendarTitle(string $title): void
    {
        $this->calendarTitle = $title;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $viewAllUrl = ProjectEventResource::canViewAny() ? ProjectEventResource::getUrl() : null;

        return [
            'heading' => $this->calendarTitle !== ''
                ? $this->calendarTitle
                : __('dashboard.project.calendar_heading'),
            'viewAllLabel' => __('dashboard.common.view_all'),
            'viewAllUrl' => $viewAllUrl,
        ];
    }
}
