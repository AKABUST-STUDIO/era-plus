<?php

namespace App\Filament\Project\Widgets;

use App\Models\Project;
use App\Models\ProjectEvent;
use Filament\Facades\Filament;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Collection;

class ProjectCalendar extends CalendarWidget
{
    protected static ?int $sort = 100;

    /**
     * @return Collection<int, CalendarEvent>
     */
    protected function getEvents(FetchInfo $info): Collection
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return new Collection;
        }

        return ProjectEvent::query()
            ->where('project_id', $project->id)
            ->whereBetween('starts_at', [$info->start, $info->end])
            ->get()
            ->map(fn (ProjectEvent $event) => CalendarEvent::make($event)
                ->title($event->title)
                ->start($event->starts_at)
                ->end($event->ends_at)
                ->allDay($event->all_day));
    }
}
