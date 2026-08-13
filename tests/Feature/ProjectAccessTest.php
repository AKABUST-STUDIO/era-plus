<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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

    public function test_org_admin_bypasses_all_project_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->joinOrganization($this->organization, OrganizationRole::Admin);

        $this->assertTrue($this->access->can($admin, 'view_any_participant', $this->project));
        $this->assertTrue($this->access->can($admin, 'create_project_user', $this->project));
        $this->assertTrue($this->access->can($admin, 'country_limits_travel_expense', $this->project));
    }

    public function test_project_admin_bypasses_all_project_permissions(): void
    {
        $projectAdmin = User::factory()->create();
        $projectAdmin->joinOrganization($this->organization);
        $projectAdmin->joinProject($this->project, ProjectRole::Admin);

        $this->assertTrue($this->access->can($projectAdmin, 'view_any_participant', $this->project));
        $this->assertTrue($this->access->can($projectAdmin, 'create_project_user', $this->project));
        $this->assertTrue($this->access->can($projectAdmin, 'country_limits_travel_expense', $this->project));
    }

    public function test_participant_granted_a_single_permission_does_not_get_others(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization);
        $participant->joinProject($this->project, ProjectRole::Participant);

        $this->project->roleFor(ProjectRole::Participant)->givePermissionTo('create_participant');

        $this->assertTrue($this->access->can($participant, 'create_participant', $this->project));
        $this->assertFalse($this->access->can($participant, 'create_project_user', $this->project));
        $this->assertFalse($this->access->can($participant, 'country_limits_travel_expense', $this->project));
    }

    public function test_default_participant_gets_only_read_permissions(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization);
        $participant->joinProject($this->project, ProjectRole::Participant);

        $this->assertTrue($this->access->can($participant, 'view_any_project_user', $this->project));
        $this->assertTrue($this->access->can($participant, 'view_any_project_event', $this->project));
        $this->assertFalse($this->access->can($participant, 'create_project_user', $this->project));
        $this->assertFalse($this->access->can($participant, 'view_any_participant', $this->project));
        $this->assertFalse($this->access->can($participant, 'create_participant', $this->project));
    }

    public function test_non_member_has_no_permissions(): void
    {
        $stranger = User::factory()->create();

        $this->assertFalse($this->access->can($stranger, 'view_any_participant', $this->project));
        $this->assertFalse($this->access->can($stranger, 'view_any_project_user', $this->project));
    }

    public function test_gates_follow_the_granted_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->joinOrganization($this->organization);
        $viewer->joinProject($this->project, ProjectRole::Participant);

        $this->project->roleFor(ProjectRole::Participant)->givePermissionTo(['view_any_participant', 'create_participant']);

        $this->actingAs($viewer);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);

        $this->assertTrue(Gate::allows('viewAny', Participant::class));
        $this->assertTrue(Gate::allows('create', Participant::class));
        $this->assertTrue(ProjectMemberResource::canViewAny());
        $this->assertFalse(ProjectMemberResource::canCreate());
    }
}
