<?php

namespace App\Filament\Project\Widgets;

use App\Models\Project;
use App\Models\ProjectEvent;
use Filament\Facades\Filament;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class ProjectCalendar extends FullCalendarWidget
{
    protected static ?int $sort = 100;

    /**
     * @param  array{start: string, end: string, timezone: string}  $info
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(array $info): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        return ProjectEvent::query()
            ->where('project_id', $project->id)
            ->whereBetween('starts_at', [$info['start'], $info['end']])
            ->get()
            ->map(fn (ProjectEvent $event): EventData => EventData::make()
                ->id((string) $event->id)
                ->title($event->title)
                ->start($event->starts_at)
                ->end($event->ends_at)
                ->allDay((bool) $event->all_day))
            ->toArray();
    }
}
