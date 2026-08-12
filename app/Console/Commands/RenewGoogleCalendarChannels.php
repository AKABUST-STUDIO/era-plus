<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\GoogleCalendar\WebhookService;
use Illuminate\Console\Command;

class RenewGoogleCalendarChannels extends Command
{
    protected $signature = 'google-calendar:renew-channels';

    protected $description = 'Renew Google Calendar push notification channels that expire within 24 hours.';

    public function handle(WebhookService $webhookService): int
    {
        $expiring = Project::query()
            ->whereNotNull('google_calendar_channel_id')
            ->where('google_calendar_channel_expires_at', '<=', now()->addDay())
            ->get();

        foreach ($expiring as $project) {
            $webhookService->renew($project);
        }

        $this->info(sprintf('Renewed %d channel(s).', $expiring->count()));

        return self::SUCCESS;
    }
}
