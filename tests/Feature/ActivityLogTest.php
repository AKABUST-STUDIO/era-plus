<?php

namespace Tests\Feature;

use App\Filament\Organization\Settings\Pages\Activity;
use App\Filament\Project\Pages\ProjectActivityLog;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Maria']);
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->user);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->project->users()->attach($this->user);

        $this->actingAs($this->user);
    }

    public function test_record_captures_actor_and_scope(): void
    {
        $entry = ActivityLog::record(
            organization: $this->organization,
            label: 'Updated organization name',
            project: null,
            eventType: 'organization.updated',
            target: $this->organization,
            data: ['from' => 'old', 'to' => 'new'],
        );

        $this->assertSame($this->organization->id, $entry->organization_id);
        $this->assertSame($this->user->id, $entry->user_id);
        $this->assertSame('organization.updated', $entry->event_type);
        $this->assertSame(['from' => 'old', 'to' => 'new'], $entry->fresh()->data);
    }

    public function test_org_activity_page_lists_org_entries_only(): void
    {
        ActivityLog::record($this->organization, 'A happened');
        $otherOrg = Organization::factory()->create();
        ActivityLog::record($otherOrg, 'Stranger event');

        Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);

        Livewire::test(Activity::class)
            ->assertCanSeeTableRecords(
                ActivityLog::query()->where('organization_id', $this->organization->id)->get()
            )
            ->assertCanNotSeeTableRecords(
                ActivityLog::query()->where('organization_id', $otherOrg->id)->get()
            );
    }

    public function test_project_activity_page_lists_project_entries_only(): void
    {
        ActivityLog::record($this->organization, 'Project event', $this->project);
        ActivityLog::record($this->organization, 'Org-only event');

        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);

        Livewire::test(ProjectActivityLog::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(
                ActivityLog::query()->where('project_id', $this->project->id)->get()
            )
            ->assertCanNotSeeTableRecords(
                ActivityLog::query()->whereNull('project_id')->get()
            );
    }
}
