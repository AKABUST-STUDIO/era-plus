<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProjectEventTest extends TestCase
{
    use RefreshDatabase;

    private FakeCalendarClient $fake;

    private User $admin;

    private User $member;

    private User $outsider;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    private function bootPanelAs(User $user): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
    }

    private function ensureCountryId(): int
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

    private function makeProjectParticipant(?string $email = null): ProjectParticipant
    {
        $participant = Participant::factory()->create([
            'name' => 'Alice',
            'email' => $email ?? 'alice@example.com',
        ]);

        return $this->project->addParticipant(
            $participant,
            $this->ensureCountryId(),
            ParticipantOrganization::create(['name' => 'Uni A']),
        );
    }

    public function test_admin_can_create_activity_and_it_calls_google(): void
    {
        $this->bootPanelAs($this->admin);

        $projectParticipant = $this->makeProjectParticipant();

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

        $this->assertNotNull($activity->fresh()->google_event_id);
        $this->assertNotNull($this->project->fresh()->google_calendar_id);
        $this->assertCount(1, ProjectEventAttendee::where('project_event_id', $activity->id)->get());

        $createCalendarCalls = array_filter($this->fake->calls, fn (array $c): bool => $c['type'] === 'createCalendar');
        $createEventCalls = array_filter($this->fake->calls, fn (array $c): bool => $c['type'] === 'createEvent');

        $this->assertCount(1, $createCalendarCalls);
        $this->assertCount(1, $createEventCalls);
    }

    public function test_outsider_cannot_view_activities_page(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('project'));
        $url = ProjectEventResource::getUrl(tenant: $this->project);
        $this->actingAs($this->outsider);

        $response = $this->get($url);

        $response->assertForbidden();
    }

    public function test_admin_can_view_activities_page(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('project'));
        $url = ProjectEventResource::getUrl(tenant: $this->project);
        $this->actingAs($this->admin);

        $response = $this->get($url);

        $response->assertSuccessful();
    }

    public function test_member_can_view_activities_page(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('project'));
        $url = ProjectEventResource::getUrl(tenant: $this->project);
        $this->actingAs($this->member);

        $response = $this->get($url);

        $response->assertSuccessful();
    }

    public function test_widget_fetches_events_within_range(): void
    {
        $this->bootPanelAs($this->admin);

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

        $this->assertCount(1, $events);
        $this->assertSame('Inside', $events[0]['title']);
    }

    public function test_update_activity_calls_update_event(): void
    {
        $this->bootPanelAs($this->admin);

        $projectParticipant = $this->makeProjectParticipant();

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
        $this->assertCount(1, $updateCalls);
    }

    public function test_delete_activity_calls_delete_event(): void
    {
        $this->bootPanelAs($this->admin);

        $projectParticipant = $this->makeProjectParticipant();

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
        $this->assertCount(1, $deleteCalls);
    }
}
