<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\ProjectEvents\ProjectEventResource;
use App\Filament\Project\Resources\ProjectEvents\Widgets\ProjectEventsCalendar;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use App\Models\Project\ProjectEvent;
use App\Models\Project\ProjectEventAttendee;
use App\Models\Project\ProjectParticipant;
use App\Models\User;
use App\Services\GoogleCalendar\Contracts\CalendarClient;
use App\Services\GoogleCalendar\EventService;
use App\Services\GoogleCalendar\FakeCalendarClient;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->fake = new FakeCalendarClient;
    $this->app->instance(CalendarClient::class, $this->fake);

    $this->organization = Organization::factory()->create();

    $this->admin = User::factory()->create();
    $this->admin->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->member = User::factory()->create();
    $this->member->joinOrganization($this->organization);

    $this->outsider = User::factory()->create();

    $this->project = Project::factory()->for($this->organization)->create([
        'name' => 'Test',
        'timezone' => 'UTC',
    ]);

    $this->admin->joinProject($this->project, ProjectRole::Admin);
    $this->member->joinProject($this->project);

    URL::defaults(['organization' => $this->organization->slug]);
});

function bootProjectPanelAs(User $user, Project $project): void
{
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($project);
}

function ensureCountryIdForEvents(): int
{
    $existing = DB::table('countries')->value('id');

    if ($existing !== null) {
        return (int) $existing;
    }

    DB::table('countries')->insert([
        'iso2' => 'ES',
        'name' => 'Spain',
        'status' => 1,
        'phone_code' => '0',
        'iso3' => 'ESP',
        'region' => 'Europe',
        'subregion' => 'Europe',
    ]);

    return (int) DB::table('countries')->where('iso2', 'ES')->value('id');
}

function makeProjectParticipantForEvents(Project $project, ?string $email = null): ProjectParticipant
{
    $participant = Participant::factory()->create([
        'name' => 'Alice',
        'email' => $email ?? 'alice@example.com',
    ]);

    return $project->addParticipant(
        $participant,
        ensureCountryIdForEvents(),
        ParticipantOrganization::create(['name' => 'Uni A']),
    );
}

test('admin can create activity and it calls google', function (): void {
    bootProjectPanelAs($this->admin, $this->project);

    $projectParticipant = makeProjectParticipantForEvents($this->project);

    $activity = new ProjectEvent([
        'project_id' => $this->project->id,
        'title' => 'Kickoff',
        'description' => 'First meeting',
        'location' => 'Madrid',
        'starts_at' => now()->addDay()->toDateTimeString(),
        'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
    ]);
    $activity->save();

    app(EventService::class)->create(
        $activity,
        [$projectParticipant->id],
    );

    expect($activity->fresh()->google_event_id)->not->toBeNull()
        ->and($this->project->fresh()->google_calendar_id)->not->toBeNull()
        ->and(ProjectEventAttendee::where('project_event_id', $activity->id)->get())->toHaveCount(1);

    $createCalendarCalls = array_filter($this->fake->calls, fn (array $c): bool => $c['type'] === 'createCalendar');
    $createEventCalls = array_filter($this->fake->calls, fn (array $c): bool => $c['type'] === 'createEvent');

    expect($createCalendarCalls)->toHaveCount(1)
        ->and($createEventCalls)->toHaveCount(1);
});

test('outsider cannot view activities page', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('project'));
    $url = ProjectEventResource::getUrl(tenant: $this->project);
    $this->actingAs($this->outsider);

    $this->get($url)->assertForbidden();
});

test('admin can view activities page', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('project'));
    $url = ProjectEventResource::getUrl(tenant: $this->project);
    $this->actingAs($this->admin);

    $this->get($url)->assertSuccessful();
});

test('member can view activities page', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('project'));
    $url = ProjectEventResource::getUrl(tenant: $this->project);
    $this->actingAs($this->member);

    $this->get($url)->assertSuccessful();
});

test('widget fetches events within range', function (): void {
    bootProjectPanelAs($this->admin, $this->project);

    ProjectEvent::factory()->for($this->project)->create([
        'title' => 'Inside',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);
    ProjectEvent::factory()->for($this->project)->create([
        'title' => 'Outside',
        'starts_at' => now()->addYear(),
        'ends_at' => now()->addYear()->addHour(),
    ]);

    $widget = new ProjectEventsCalendar;
    $events = $widget->fetchEvents([
        'start' => now()->toIso8601String(),
        'end' => now()->addWeek()->toIso8601String(),
        'timezone' => 'UTC',
    ]);

    expect($events)->toHaveCount(1)
        ->and($events[0]['title'])->toBe('Inside');
});

test('update activity calls update event', function (): void {
    bootProjectPanelAs($this->admin, $this->project);

    $projectParticipant = makeProjectParticipantForEvents($this->project);

    $activity = new ProjectEvent([
        'project_id' => $this->project->id,
        'title' => 'Meeting',
        'starts_at' => now()->addDay()->toDateTimeString(),
        'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
    ]);
    $activity->save();

    $service = app(EventService::class);
    $service->create($activity, [$projectParticipant->id]);

    $activity->refresh();
    $activity->title = 'Updated Meeting';
    $activity->save();

    $service->update($activity, [$projectParticipant->id]);

    $updateCalls = array_filter($this->fake->calls, fn (array $c): bool => $c['type'] === 'updateEvent');
    expect($updateCalls)->toHaveCount(1);
});

test('delete activity calls delete event', function (): void {
    bootProjectPanelAs($this->admin, $this->project);

    $projectParticipant = makeProjectParticipantForEvents($this->project);

    $activity = new ProjectEvent([
        'project_id' => $this->project->id,
        'title' => 'Meeting',
        'starts_at' => now()->addDay()->toDateTimeString(),
        'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
    ]);
    $activity->save();

    $service = app(EventService::class);
    $service->create($activity, [$projectParticipant->id]);
    $service->delete($activity);

    $deleteCalls = array_filter($this->fake->calls, fn (array $c): bool => $c['type'] === 'deleteEvent');
    expect($deleteCalls)->toHaveCount(1);
});

test('all_participants toggle defaults to true and hides the participant select', function (): void {
    bootProjectPanelAs($this->admin, $this->project);

    Livewire::test(ProjectEventsCalendar::class)
        ->mountAction('create')
        ->assertActionMounted('create')
        ->assertSchemaStateSet(['all_participants' => true], 'mountedActionSchema0');
});

test('switching all_participants off exposes the participant select and requires it', function (): void {
    bootProjectPanelAs($this->admin, $this->project);

    Livewire::test(ProjectEventsCalendar::class)
        ->mountAction('create')
        ->fillForm([
            'all_participants' => false,
            'title' => 'Meeting',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
        ], 'mountedActionSchema0')
        ->callMountedAction()
        ->assertHasFormErrors(['participable_refs'], 'mountedActionSchema0');
});

test('creating an event with all_participants true attaches every project participant', function (): void {
    bootProjectPanelAs($this->admin, $this->project);

    $projectParticipant = makeProjectParticipantForEvents($this->project);

    Livewire::test(ProjectEventsCalendar::class)
        ->callAction('create', data: [
            'title' => 'All hands',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
            'all_participants' => true,
        ])
        ->assertHasNoActionErrors();

    $event = ProjectEvent::query()->where('title', 'All hands')->firstOrFail();

    expect(ProjectEventAttendee::query()->where('project_event_id', $event->id)->pluck('project_participant_id')->all())
        ->toContain($projectParticipant->id);
});
