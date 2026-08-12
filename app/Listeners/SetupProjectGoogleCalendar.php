<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Services\GoogleCalendar\CalendarService;
use App\Services\GoogleCalendar\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SetupProjectGoogleCalendar implements ShouldQueue
{
    public string $queue = 'google-calendar';

    public function __construct(
        private readonly CalendarService $calendarService,
        private readonly WebhookService $webhookService,
    ) {}

    public function handle(ProjectCreated $event): void
    {
        if (! $this->calendarService->isConfigured()) {
            return;
        }

        try {
            $this->calendarService->ensureCalendarForProject($event->project);
            $this->webhookService->subscribe($event->project);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
