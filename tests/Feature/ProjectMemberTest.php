<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\ProjectMembers\Pages\CreateProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\EditProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
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
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->user->joinProject($this->project, ProjectRole::Admin);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_list_page_loads_and_shows_member(): void
    {
        Livewire::test(ListProjectMembers::class)
            ->assertSuccessful()
            ->assertSee($this->user->name)
            ->assertSee('Project Admin');
    }

    public function test_list_only_shows_current_project_members(): void
    {
        $otherProject = Project::factory()->for($this->organization)->create();
        $strangerInOtherProject = User::factory()->create();
        $strangerInOtherProject->joinOrganization($this->organization);
        $strangerInOtherProject->joinProject($otherProject, ProjectRole::Participant);

        Livewire::test(ListProjectMembers::class)
            ->assertSee($this->user->name)
            ->assertDontSee($strangerInOtherProject->name);
    }

    public function test_can_add_org_member_to_project(): void
    {
        $newMember = User::factory()->create();
        $newMember->joinOrganization($this->organization);

        Livewire::test(CreateProjectMember::class)
            ->fillForm([
                'user_id' => $newMember->id,
                'role' => ProjectRole::Admin->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $newMember->id,
            'role_id' => $this->project->roleFor(ProjectRole::Admin)->id,
        ]);
    }

    public function test_can_change_member_role(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization);
        $member->joinProject($this->project, ProjectRole::Participant);

        $pivot = ProjectUser::query()
            ->where('user_id', $member->id)
            ->first();

        Livewire::test(EditProjectMember::class, ['record' => $pivot->getRouteKey()])
            ->fillForm(['role' => ProjectRole::Admin->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $member->id,
            'role_id' => $this->project->roleFor(ProjectRole::Admin)->id,
        ]);
    }

    public function test_can_remove_member(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization);
        $member->joinProject($this->project, ProjectRole::Participant);

        $pivot = ProjectUser::query()->where('user_id', $member->id)->first();

        Livewire::test(EditProjectMember::class, ['record' => $pivot->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_creator_is_attached_as_project_admin(): void
    {
        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role_id' => $this->project->roleFor(ProjectRole::Admin)->id,
        ]);
    }
}
