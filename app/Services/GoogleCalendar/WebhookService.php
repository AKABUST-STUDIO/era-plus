<?php

declare(strict_types=1);

namespace App\Services\GoogleCalendar;

use App\Models\Project;
use App\Services\GoogleCalendar\Contracts\CalendarClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WebhookService
{
    public function __construct(private readonly CalendarClient $client) {}

    public function subscribe(Project $project): ?Project
    {
        if (empty($project->google_calendar_id)) {
            return null;
        }

        $webhookUrl = route('webhooks.google-calendar');

        if (! str_starts_with($webhookUrl, 'https://')) {
            return null;
        }

        $this->unsubscribe($project);

        $channelId = (string) Str::uuid();

        $response = $this->client->watchEvents($project->google_calendar_id, [
            'id' => $channelId,
            'type' => 'web_hook',
            'address' => $webhookUrl,
        ]);

        $expires = $response['expiration'] ?? null;

        $project->forceFill([
            'google_calendar_channel_id' => (string) ($response['id'] ?? $channelId),
            'google_calendar_channel_resource_id' => (string) ($response['resourceId'] ?? ''),
            'google_calendar_channel_expires_at' => $expires !== null
                ? Carbon::createFromTimestampMs((int) $expires)
                : Carbon::now()->addDays(7),
        ])->save();

        return $project->fresh();
    }

    public function unsubscribe(Project $project): void
    {
        if (empty($project->google_calendar_channel_id)) {
            return;
        }

        $this->client->stopChannel([
            'id' => $project->google_calendar_channel_id,
            'resourceId' => $project->google_calendar_channel_resource_id,
        ]);

        $project->forceFill([
            'google_calendar_channel_id' => null,
            'google_calendar_channel_resource_id' => null,
            'google_calendar_channel_expires_at' => null,
        ])->save();
    }

    public function renew(Project $project): ?Project
    {
        return $this->subscribe($project);
    }
}
