<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        OrganizationService::remember($this->organization);
        ProjectService::remember($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    private function membership(User $user): ProjectUser
    {
        return ProjectUser::query()
            ->where('project_id', $this->project->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function test_page_loads_and_lists_the_member(): void
    {
        Livewire::test(ListProjectMembers::class)
            ->assertOk()
            ->assertSee($this->user->name)
            ->assertSee(ProjectRole::Admin->getLabel());
    }

    public function test_table_lists_only_members_of_the_current_project(): void
    {
        $otherProject = Project::factory()->for($this->organization)->create();
        $stranger = User::factory()->create();
        $stranger->joinOrganization($this->organization);
        $stranger->joinProject($otherProject);

        Livewire::test(ListProjectMembers::class)
            ->assertCanSeeTableRecords([$this->membership($this->user)])
            ->assertSee($this->user->name)
            ->assertDontSee($stranger->name);
    }

    public function test_invite_requires_an_email(): void
    {
        Livewire::test(ListProjectMembers::class)
            ->callAction('create', data: ['email' => ''])
            ->assertHasFormErrors(['email' => 'required']);
    }

    public function test_invite_adds_the_user_to_the_project(): void
    {
        Livewire::test(ListProjectMembers::class)
            ->callAction('create', data: [
                'email' => 'newcomer@example.com',
                'role' => $this->project->roleFor(ProjectRole::Participant)->id,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $invitee = User::query()->where('email', 'newcomer@example.com')->firstOrFail();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $invitee->id,
            'role_id' => $this->project->roleFor(ProjectRole::Participant)->id,
        ]);
    }

    public function test_invite_also_adds_the_user_to_the_organization(): void
    {
        Notification::fake();

        Livewire::test(ListProjectMembers::class)
            ->callAction('create', data: [
                'email' => 'newcomer@example.com',
                'role' => $this->project->roleFor(ProjectRole::Participant)->id,
            ])
            ->assertHasNoFormErrors();

        $invitee = User::query()->where('email', 'newcomer@example.com')->firstOrFail();

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $this->organization->id,
            'user_id' => $invitee->id,
            'role_id' => $this->organization->roleFor(OrganizationRole::Member)->id,
        ]);

        Notification::assertSentTo($invitee, OrganizationInvitationNotification::class);
    }

    public function test_invite_keeps_the_existing_organization_role(): void
    {
        Notification::fake();

        $existing = User::factory()->create();
        $existing->joinOrganization($this->organization, OrganizationRole::Admin);

        Livewire::test(ListProjectMembers::class)
            ->callAction('create', data: [
                'email' => $existing->email,
                'role' => $this->project->roleFor(ProjectRole::Participant)->id,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(1, $this->organization->users()->whereKey($existing->id)->count());
        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $this->organization->id,
            'user_id' => $existing->id,
            'role_id' => $this->organization->roleFor(OrganizationRole::Admin)->id,
        ]);

        Notification::assertNothingSentTo($existing);
    }

    public function test_inviting_an_existing_project_member_warns_instead_of_duplicating(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization);
        $member->joinProject($this->project);

        Livewire::test(ListProjectMembers::class)
            ->callAction('create', data: [
                'email' => $member->email,
                'role' => $this->project->roleFor(ProjectRole::Admin)->id,
            ]);

        $this->assertSame(1, ProjectUser::query()
            ->where('project_id', $this->project->id)
            ->where('user_id', $member->id)
            ->count());
    }

    public function test_can_change_member_role(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization);
        $member->joinProject($this->project);

        Livewire::test(ListProjectMembers::class)
            ->callAction(
                TestAction::make('changeRole')->table($this->membership($member)),
                data: ['role' => $this->project->roleFor(ProjectRole::Admin)->id],
            );

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
        $member->joinProject($this->project);

        Livewire::test(ListProjectMembers::class)
            ->callAction(TestAction::make('delete')->table($this->membership($member)));

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_participant_can_view_but_cannot_invite(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization);
        $participant->joinProject($this->project);

        $this->actingAs($participant);

        Livewire::test(ListProjectMembers::class)
            ->assertOk()
            ->assertActionHidden('create');
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
