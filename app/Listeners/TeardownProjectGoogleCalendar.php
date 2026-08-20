<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProjectDeleting;
use App\Services\GoogleCalendar\CalendarService;
use App\Services\GoogleCalendar\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class TeardownProjectGoogleCalendar implements ShouldQueue
{
    public function __construct(
        private readonly CalendarService $calendarService,
        private readonly WebhookService $webhookService,
    ) {}

    public function viaQueue(): string
    {
        return config('queue.names.google');
    }

    public function handle(ProjectDeleting $event): void
    {
        if (! $event->project->isForceDeleting()) {
            return;
        }

        if (! $this->calendarService->isConfigured()) {
            return;
        }

        try {
            $this->webhookService->unsubscribe($event->project);
            $this->calendarService->deleteCalendarForProject($event->project);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
