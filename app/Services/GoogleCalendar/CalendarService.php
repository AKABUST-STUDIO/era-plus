<?php

declare(strict_types=1);

namespace App\Services\GoogleCalendar;

use App\Models\Project;
use App\Services\GoogleCalendar\Contracts\CalendarClient;
use App\Support\GoogleCalendarCredentials;

class CalendarService
{
    public function __construct(private readonly CalendarClient $client) {}

    public function isConfigured(): bool
    {
        return GoogleCalendarCredentials::isConfigured();
    }

    public function ensureCalendarForProject(Project $project): string
    {
        if (! empty($project->google_calendar_id)) {
            return $project->google_calendar_id;
        }

        $this->client->setSubject(GoogleCalendarCredentials::impersonate());

        $result = $this->client->createCalendar([
            'summary' => $project->name,
            'timeZone' => $project->timezone ?: 'UTC',
            'location' => $project->location,
        ]);

        $calendarId = (string) ($result['id'] ?? '');

        $project->forceFill(['google_calendar_id' => $calendarId])->save();

        return $calendarId;
    }

    public function deleteCalendarForProject(Project $project): void
    {
        if (empty($project->google_calendar_id)) {
            return;
        }

        $this->client->setSubject(GoogleCalendarCredentials::impersonate());
        $this->client->deleteCalendar($project->google_calendar_id);

        $project->forceFill(['google_calendar_id' => null])->save();
    }
}
