<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Pages\Activity;
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
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->user->joinProject($this->project);

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
        $this->assertSame($this->user->id, $entry->causer_id);
        $this->assertSame('organization.updated', $entry->event);
        $this->assertSame(['from' => 'old', 'to' => 'new'], $entry->fresh()->properties->toArray());
    }

    public function test_org_activity_page_lists_org_entries_only(): void
    {
        ActivityLog::record($this->organization, 'A happened');
        $otherOrg = Organization::factory()->create();
        ActivityLog::record($otherOrg, 'Stranger event');

        Filament::setCurrentPanel(Filament::getPanel('organization'));
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

    public function test_date_filter_narrows_activity_to_the_range(): void
    {
        $early = ActivityLog::record($this->organization, 'Early event');
        $early->forceFill(['created_at' => '2026-01-10 09:00:00'])->save();

        $late = ActivityLog::record($this->organization, 'Late event');
        $late->forceFill(['created_at' => '2026-06-20 09:00:00'])->save();

        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);

        Livewire::test(Activity::class)
            ->filterTable('group', ['from' => '2026-05-01'])
            ->assertCanSeeTableRecords([$late])
            ->assertCanNotSeeTableRecords([$early])
            ->filterTable('group', ['from' => null, 'until' => '2026-02-01'])
            ->assertCanSeeTableRecords([$early])
            ->assertCanNotSeeTableRecords([$late]);
    }
}
