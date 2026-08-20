<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\GoogleCalendar\EventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProjectCalendarJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $projectId)
    {
        $this->onQueue(config('queue.names.google'));
    }

    public function handle(EventService $eventService): void
    {
        $project = Project::query()->find($this->projectId);

        if ($project === null) {
            return;
        }

        $eventService->syncAllForProject($project);
    }
}
