<?php

namespace Tests\Feature;

use App\Jobs\SyncProjectCalendarJob;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleCalendarWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_channel_dispatches_sync_job(): void
    {
        Queue::fake();

        $organization = Organization::factory()->create();
        $project = Project::factory()->for($organization)->create([
            'google_calendar_channel_id' => 'chan-123',
            'google_calendar_channel_resource_id' => 'res-456',
            'google_calendar_channel_expires_at' => now()->addDays(7),
        ]);

        $response = $this->postJson('/webhooks/google-calendar', [], [
            'X-Goog-Channel-Id' => 'chan-123',
            'X-Goog-Resource-State' => 'exists',
        ]);

        $response->assertOk();
        Queue::assertPushed(SyncProjectCalendarJob::class, fn (SyncProjectCalendarJob $job): bool => $job->projectId === $project->id
            && $job->queue === 'google-calendar-webhook');
    }

    public function test_sync_state_does_not_dispatch(): void
    {
        Queue::fake();

        $organization = Organization::factory()->create();
        Project::factory()->for($organization)->create([
            'google_calendar_channel_id' => 'chan-init',
            'google_calendar_channel_resource_id' => 'res-init',
            'google_calendar_channel_expires_at' => now()->addDays(7),
        ]);

        $response = $this->postJson('/webhooks/google-calendar', [], [
            'X-Goog-Channel-Id' => 'chan-init',
            'X-Goog-Resource-State' => 'sync',
        ]);

        $response->assertOk();
        Queue::assertNotPushed(SyncProjectCalendarJob::class);
    }

    public function test_unknown_channel_returns_404(): void
    {
        $response = $this->postJson('/webhooks/google-calendar', [], [
            'X-Goog-Channel-Id' => 'unknown-channel',
        ]);

        $response->assertNotFound();
    }

    public function test_missing_channel_id_returns_400(): void
    {
        $response = $this->postJson('/webhooks/google-calendar');

        $response->assertStatus(400);
    }
}
