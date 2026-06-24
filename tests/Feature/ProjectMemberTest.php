<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Filament\Project\Resources\ProjectMembers\Pages\CreateProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\EditProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
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
        $this->organization->users()->attach($this->user);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->project->users()->attach($this->user, ['role' => ProjectRole::Coordinator->value]);

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
            ->assertSee('Coordinator');
    }

    public function test_list_only_shows_current_project_members(): void
    {
        $otherProject = Project::factory()->for($this->organization)->create();
        $strangerInOtherProject = User::factory()->create();
        $this->organization->users()->attach($strangerInOtherProject);
        $otherProject->users()->attach($strangerInOtherProject, ['role' => ProjectRole::Participant->value]);

        Livewire::test(ListProjectMembers::class)
            ->assertSee($this->user->name)
            ->assertDontSee($strangerInOtherProject->name);
    }

    public function test_can_add_org_member_to_project(): void
    {
        $newMember = User::factory()->create();
        $this->organization->users()->attach($newMember);

        Livewire::test(CreateProjectMember::class)
            ->fillForm([
                'user_id' => $newMember->id,
                'role' => ProjectRole::Leader->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $newMember->id,
            'role' => ProjectRole::Leader->value,
        ]);
    }

    public function test_can_change_member_role(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member);
        $this->project->users()->attach($member, ['role' => ProjectRole::Participant->value]);

        $pivot = ProjectMember::query()
            ->where('user_id', $member->id)
            ->first();

        Livewire::test(EditProjectMember::class, ['record' => $pivot->getRouteKey()])
            ->fillForm(['role' => ProjectRole::Leader->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $member->id,
            'role' => ProjectRole::Leader->value,
        ]);
    }

    public function test_can_remove_member(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member);
        $this->project->users()->attach($member, ['role' => ProjectRole::Participant->value]);

        $pivot = ProjectMember::query()->where('user_id', $member->id)->first();

        Livewire::test(EditProjectMember::class, ['record' => $pivot->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_creator_is_attached_as_coordinator(): void
    {
        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role' => ProjectRole::Coordinator->value,
        ]);
    }
}
