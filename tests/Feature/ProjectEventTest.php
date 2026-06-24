<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Filament\Project\Resources\ProjectEvents\Pages\CreateProjectEvent;
use App\Filament\Project\Resources\ProjectEvents\Pages\ListProjectEvents;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectEventTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->user, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->project->users()->attach($this->user, ['role' => ProjectRole::Coordinator->value]);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_list_loads(): void
    {
        Livewire::test(ListProjectEvents::class)->assertSuccessful();
    }

    public function test_event_can_be_created(): void
    {
        Livewire::test(CreateProjectEvent::class)
            ->fillForm([
                'title' => 'Kickoff meeting',
                'starts_at' => '2026-09-01 10:00:00',
                'ends_at' => '2026-09-01 12:00:00',
                'location' => 'Tallinn',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('project_events', [
            'project_id' => $this->project->id,
            'title' => 'Kickoff meeting',
            'location' => 'Tallinn',
        ]);
    }

    public function test_end_must_be_after_start(): void
    {
        Livewire::test(CreateProjectEvent::class)
            ->fillForm([
                'title' => 'Bad event',
                'starts_at' => '2026-09-01 10:00:00',
                'ends_at' => '2026-09-01 09:00:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['ends_at']);
    }

    public function test_google_calendar_url_contains_title(): void
    {
        $event = ProjectEvent::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'title' => 'Workshop',
            'starts_at' => '2026-10-15 14:00:00',
            'ends_at' => '2026-10-15 16:00:00',
            'location' => 'Berlin',
        ]);

        $url = $event->googleCalendarUrl();

        $this->assertStringContainsString('action=TEMPLATE', $url);
        $this->assertStringContainsString('Workshop', $url);
        $this->assertStringContainsString('Berlin', $url);
    }

    public function test_events_isolated_by_project(): void
    {
        $other = Project::factory()->for($this->organization)->create();
        ProjectEvent::factory()->count(2)->create([
            'project_id' => $other->id,
            'organization_id' => $this->organization->id,
        ]);
        ProjectEvent::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
        ]);

        $this->assertCount(3, ProjectEvent::query()->get());
    }
}
