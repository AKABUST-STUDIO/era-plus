<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Filament\Project\Resources\FinanceEntries\FinanceEntryResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Project $project;

    private ProjectAccess $access;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->project = Project::factory()->for($this->organization)->create();

        $this->access = app(ProjectAccess::class);
    }

    public function test_org_admin_bypasses_all_abilities(): void
    {
        $admin = User::factory()->create();
        $this->organization->users()->attach($admin, ['role' => OrganizationRole::Admin->value, 'is_admin' => true]);

        $this->assertTrue($this->access->can($admin, ProjectAccess::ABILITY_VIEW_FINANCE, $this->project));
        $this->assertTrue($this->access->can($admin, ProjectAccess::ABILITY_MANAGE_FINANCE, $this->project));
        $this->assertTrue($this->access->can($admin, ProjectAccess::ABILITY_MANAGE_MEMBERS, $this->project));
    }

    public function test_coordinator_can_manage_finance(): void
    {
        $coordinator = User::factory()->create();
        $this->organization->users()->attach($coordinator);
        $this->project->users()->attach($coordinator, ['role' => ProjectRole::Coordinator->value]);

        $this->assertTrue($this->access->can($coordinator, ProjectAccess::ABILITY_VIEW_FINANCE, $this->project));
        $this->assertTrue($this->access->can($coordinator, ProjectAccess::ABILITY_MANAGE_FINANCE, $this->project));
        $this->assertTrue($this->access->can($coordinator, ProjectAccess::ABILITY_MANAGE_MEMBERS, $this->project));
    }

    public function test_leader_can_view_finance_but_not_manage(): void
    {
        $leader = User::factory()->create();
        $this->organization->users()->attach($leader);
        $this->project->users()->attach($leader, ['role' => ProjectRole::Leader->value]);

        $this->assertTrue($this->access->can($leader, ProjectAccess::ABILITY_VIEW_FINANCE, $this->project));
        $this->assertFalse($this->access->can($leader, ProjectAccess::ABILITY_MANAGE_FINANCE, $this->project));
        $this->assertFalse($this->access->can($leader, ProjectAccess::ABILITY_MANAGE_MEMBERS, $this->project));
    }

    public function test_participant_cannot_view_finance(): void
    {
        $participant = User::factory()->create();
        $this->organization->users()->attach($participant);
        $this->project->users()->attach($participant, ['role' => ProjectRole::Participant->value]);

        $this->assertFalse($this->access->can($participant, ProjectAccess::ABILITY_VIEW_FINANCE, $this->project));
        $this->assertFalse($this->access->can($participant, ProjectAccess::ABILITY_MANAGE_FINANCE, $this->project));
    }

    public function test_non_member_has_no_abilities(): void
    {
        $stranger = User::factory()->create();

        $this->assertFalse($this->access->can($stranger, ProjectAccess::ABILITY_VIEW_FINANCE, $this->project));
        $this->assertFalse($this->access->can($stranger, ProjectAccess::ABILITY_MANAGE_FINANCE, $this->project));
    }

    public function test_finance_resource_can_view_any_uses_gate(): void
    {
        $leader = User::factory()->create();
        $this->organization->users()->attach($leader);
        $this->project->users()->attach($leader, ['role' => ProjectRole::Leader->value]);

        $this->actingAs($leader);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);

        $this->assertTrue(FinanceEntryResource::canViewAny());
        $this->assertFalse(FinanceEntryResource::canCreate());
    }

    public function test_finance_resource_denies_participant(): void
    {
        $participant = User::factory()->create();
        $this->organization->users()->attach($participant);
        $this->project->users()->attach($participant, ['role' => ProjectRole::Participant->value]);

        $this->actingAs($participant);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);

        $this->assertFalse(FinanceEntryResource::canViewAny());
    }
}
