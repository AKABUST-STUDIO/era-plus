<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Settings\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Project\Settings\Resources\ProjectMembers\ProjectMemberResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->project = Project::factory()->for($this->organization)->create();

    URL::defaults(['organization' => $this->organization->slug]);
});

test('demoting admin to participant revokes admin permissions', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->organization, OrganizationRole::Member);
    $user->joinProject($this->project, ProjectRole::Admin);

    $access = app(ProjectAccess::class);
    $this->assertTrue($access->can($user, 'create_project_user', $this->project));

    $user->joinProject($this->project, ProjectRole::Participant);

    $this->assertFalse($access->can($user->fresh(), 'create_project_user', $this->project));
});

test('promoting participant to admin grants admin permissions', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->organization, OrganizationRole::Member);
    $user->joinProject($this->project, ProjectRole::Participant);

    $access = app(ProjectAccess::class);
    $this->assertFalse($access->can($user, 'create_project_user', $this->project));

    $user->joinProject($this->project, ProjectRole::Admin);

    $this->assertTrue($access->can($user->fresh(), 'create_project_user', $this->project));
});

test('removing from project revokes all project access', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->organization, OrganizationRole::Member);
    $user->joinProject($this->project, ProjectRole::Participant);

    $this->assertTrue($user->canAccessTenant($this->project));

    ProjectUser::query()
        ->where('project_id', $this->project->id)
        ->where('user_id', $user->id)
        ->delete();

    $this->assertFalse($user->fresh()->canAccessTenant($this->project));
});

test('removing from project makes deep urls return forbidden', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->organization, OrganizationRole::Member);
    $user->joinProject($this->project, ProjectRole::Participant);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('project'));

    $url = ProjectMemberResource::getUrl(tenant: $this->project);
    $this->get($url)->assertSuccessful();

    ProjectUser::query()
        ->where('project_id', $this->project->id)
        ->where('user_id', $user->id)
        ->delete();

    $status = $this->get($url)->status();
    $this->assertTrue(in_array($status, [403, 404], true), "expected 403|404 after removal, got {$status}");
});

test('removing from org admin no longer manages projects', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->organization, OrganizationRole::Admin);
    $user->joinProject($this->project, ProjectRole::Admin);

    $access = app(ProjectAccess::class);
    $this->assertTrue($access->can($user, 'create_project_user', $this->project));

    OrganizationUser::query()
        ->where('organization_id', $this->organization->id)
        ->where('user_id', $user->id)
        ->delete();

    $freshUser = $user->fresh();
    $this->assertFalse($access->administersOrganization($freshUser, $this->organization));
});

test('demoted admin livewire page hides actions', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->organization, OrganizationRole::Member);
    $user->joinProject($this->project, ProjectRole::Admin);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->project);

    Livewire::test(ListProjectMembers::class)->assertActionVisible('create');

    $user->joinProject($this->project, ProjectRole::Participant);

    $this->actingAs($user->fresh());

    Livewire::test(ListProjectMembers::class)->assertActionHidden('create');
});

test('org admin demoted to member loses project edit gate', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);

    $this->assertTrue(ProjectResource::canEdit($this->project));

    $user->joinOrganization($this->organization, OrganizationRole::Member);

    $this->actingAs($user->fresh());
    Filament::setTenant($this->organization);

    $this->assertFalse(ProjectResource::canEdit($this->project));
});
