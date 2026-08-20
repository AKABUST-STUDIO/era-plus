<?php

use App\Jobs\SyncProjectCalendarJob;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Support\Facades\Queue;

test('valid channel dispatches sync job', function (): void {
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
});

test('sync state does not dispatch', function (): void {
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
});

test('unknown channel returns 404', function (): void {
    $response = $this->postJson('/webhooks/google-calendar', [], [
        'X-Goog-Channel-Id' => 'unknown-channel',
    ]);

    $response->assertNotFound();
});

test('missing channel id returns 400', function (): void {
    $response = $this->postJson('/webhooks/google-calendar');

    $response->assertStatus(400);
});
