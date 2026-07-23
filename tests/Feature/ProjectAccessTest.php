<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\Participants\ParticipantResource;
use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
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
        $admin->joinOrganization($this->organization, OrganizationRole::Admin);

        $this->assertTrue($this->access->can($admin, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS, $this->project));
        $this->assertTrue($this->access->can($admin, ProjectAccess::ABILITY_MANAGE_MEMBERS, $this->project));
        $this->assertTrue($this->access->can($admin, ProjectAccess::ABILITY_MANAGE_SETTINGS, $this->project));
    }

    public function test_project_admin_can_manage_participants(): void
    {
        $projectAdmin = User::factory()->create();
        $projectAdmin->joinOrganization($this->organization);
        $projectAdmin->joinProject($this->project, ProjectRole::Admin);

        $this->assertTrue($this->access->can($projectAdmin, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS, $this->project));
        $this->assertTrue($this->access->can($projectAdmin, ProjectAccess::ABILITY_MANAGE_MEMBERS, $this->project));
        $this->assertTrue($this->access->can($projectAdmin, ProjectAccess::ABILITY_MANAGE_SETTINGS, $this->project));
    }

    public function test_participant_granted_manage_participants_cannot_manage_members(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization);
        $participant->joinProject($this->project, ProjectRole::Participant);

        $this->project->roleFor(ProjectRole::Participant)->givePermissionTo(ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);

        $this->assertTrue($this->access->can($participant, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS, $this->project));
        $this->assertFalse($this->access->can($participant, ProjectAccess::ABILITY_MANAGE_MEMBERS, $this->project));
        $this->assertFalse($this->access->can($participant, ProjectAccess::ABILITY_MANAGE_SETTINGS, $this->project));
    }

    public function test_participant_cannot_manage_participants(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization);
        $participant->joinProject($this->project, ProjectRole::Participant);

        $this->assertFalse($this->access->can($participant, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS, $this->project));
        $this->assertFalse($this->access->can($participant, ProjectAccess::ABILITY_MANAGE_MEMBERS, $this->project));
    }

    public function test_non_member_has_no_abilities(): void
    {
        $stranger = User::factory()->create();

        $this->assertFalse($this->access->can($stranger, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS, $this->project));
        $this->assertFalse($this->access->can($stranger, ProjectAccess::ABILITY_MANAGE_MEMBERS, $this->project));
    }

    public function test_resource_gates_follow_the_granted_ability(): void
    {
        $viewer = User::factory()->create();
        $viewer->joinOrganization($this->organization);
        $viewer->joinProject($this->project, ProjectRole::Participant);

        $this->project->roleFor(ProjectRole::Participant)->givePermissionTo(ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);

        $this->actingAs($viewer);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);

        $this->assertTrue(ParticipantResource::canViewAny());
        $this->assertTrue(ParticipantResource::canCreate());
        $this->assertFalse(ProjectMemberResource::canViewAny());
        $this->assertFalse(ProjectMemberResource::canCreate());
    }

    public function test_participant_resource_denies_participant(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization);
        $participant->joinProject($this->project, ProjectRole::Participant);

        $this->actingAs($participant);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);

        $this->assertFalse(ParticipantResource::canViewAny());
    }
}
