<?php

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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
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
});

function membershipFor(User $user, Project $project): ProjectUser
{
    return ProjectUser::query()
        ->where('project_id', $project->id)
        ->where('user_id', $user->id)
        ->firstOrFail();
}

test('page loads and lists the member', function (): void {
    Livewire::test(ListProjectMembers::class)
        ->assertOk()
        ->assertSee($this->user->name)
        ->assertSee(ProjectRole::Admin->getLabel());
});

test('table lists only members of the current project', function (): void {
    $otherProject = Project::factory()->for($this->organization)->create();
    $stranger = User::factory()->create();
    $stranger->joinOrganization($this->organization);
    $stranger->joinProject($otherProject);

    Livewire::test(ListProjectMembers::class)
        ->assertCanSeeTableRecords([membershipFor($this->user, $this->project)])
        ->assertSee($this->user->name)
        ->assertDontSee($stranger->name);
});

test('invite requires an email', function (): void {
    Livewire::test(ListProjectMembers::class)
        ->callAction('create', data: ['email' => ''])
        ->assertHasFormErrors(['email' => 'required']);
});

test('invite adds the user to the project', function (): void {
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
});

test('invite also adds the user to the organization', function (): void {
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
});

test('invite keeps the existing organization role', function (): void {
    Notification::fake();

    $existing = User::factory()->create();
    $existing->joinOrganization($this->organization, OrganizationRole::Admin);

    Livewire::test(ListProjectMembers::class)
        ->callAction('create', data: [
            'email' => $existing->email,
            'role' => $this->project->roleFor(ProjectRole::Participant)->id,
        ])
        ->assertHasNoFormErrors();

    expect($this->organization->users()->whereKey($existing->id)->count())->toBe(1);
    $this->assertDatabaseHas('organization_users', [
        'organization_id' => $this->organization->id,
        'user_id' => $existing->id,
        'role_id' => $this->organization->roleFor(OrganizationRole::Admin)->id,
    ]);

    Notification::assertNothingSentTo($existing);
});

test('inviting an existing project member warns instead of duplicating', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization);
    $member->joinProject($this->project);

    Livewire::test(ListProjectMembers::class)
        ->callAction('create', data: [
            'email' => $member->email,
            'role' => $this->project->roleFor(ProjectRole::Admin)->id,
        ]);

    expect(ProjectUser::query()
        ->where('project_id', $this->project->id)
        ->where('user_id', $member->id)
        ->count())->toBe(1);
});

test('can change member role', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization);
    $member->joinProject($this->project);

    Livewire::test(ListProjectMembers::class)
        ->callAction(
            TestAction::make('changeRole')->table(membershipFor($member, $this->project)),
            data: ['role' => $this->project->roleFor(ProjectRole::Admin)->id],
        );

    $this->assertDatabaseHas('project_user', [
        'project_id' => $this->project->id,
        'user_id' => $member->id,
        'role_id' => $this->project->roleFor(ProjectRole::Admin)->id,
    ]);
});

test('can remove member', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization);
    $member->joinProject($this->project);

    Livewire::test(ListProjectMembers::class)
        ->callAction(TestAction::make('delete')->table(membershipFor($member, $this->project)));

    $this->assertDatabaseMissing('project_user', [
        'project_id' => $this->project->id,
        'user_id' => $member->id,
    ]);
});

test('participant can view but cannot invite', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization);
    $participant->joinProject($this->project);

    $this->actingAs($participant);

    Livewire::test(ListProjectMembers::class)
        ->assertOk()
        ->assertActionHidden('create');
});

test('creator is attached as project admin', function (): void {
    $this->assertDatabaseHas('project_user', [
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'role_id' => $this->project->roleFor(ProjectRole::Admin)->id,
    ]);
});
