<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Settings\Resources\Roles\Pages\ListRoles;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->project = Project::factory()->for($this->organization)->create();

    $this->admin->joinOrganization($this->organization, OrganizationRole::Admin);
    $this->admin->joinProject($this->project, ProjectRole::Admin);

    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('project.settings'));
    Filament::setTenant($this->project);
    URL::defaults([
        'organization' => $this->organization->slug,
        'project' => $this->project->slug,
    ]);
});

test('admin can load list page', function (): void {
    Livewire::test(ListRoles::class)->assertSuccessful();
});

test('non admin cannot load list page', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization, OrganizationRole::Member);
    $participant->joinProject($this->project, ProjectRole::Participant);

    $this->actingAs($participant);

    Livewire::test(ListRoles::class)->assertForbidden();
});

test('admin member and participant roles are seeded and all locked', function (): void {
    $this->assertNotNull($this->project->roleFor(ProjectRole::Admin));
    $this->assertNotNull($this->project->roleFor(ProjectRole::Member));
    $this->assertNotNull($this->project->roleFor(ProjectRole::Participant));
    $this->assertTrue($this->project->roleFor(ProjectRole::Admin)->locked);
    $this->assertTrue($this->project->roleFor(ProjectRole::Member)->locked);
    $this->assertTrue($this->project->roleFor(ProjectRole::Participant)->locked);
});

test('delete row action is hidden for member role', function (): void {
    $memberRole = $this->project->roleFor(ProjectRole::Member);

    Livewire::test(ListRoles::class)
        ->assertTableActionHidden('delete', $memberRole);
});

test('delete row action is hidden for participant role', function (): void {
    $participantRole = $this->project->roleFor(ProjectRole::Participant);

    Livewire::test(ListRoles::class)
        ->assertTableActionHidden('delete', $participantRole);
});

test('built in admin role is listed', function (): void {
    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords(
            $this->project->roles()->get()->all(),
        );
});

test('create role modal persists role and redirects to edit', function (): void {
    Livewire::test(ListRoles::class)
        ->callAction('create', data: ['name' => 'coordinator'])
        ->assertHasNoActionErrors()
        ->assertRedirect();

    $role = $this->project->roles()->where('name', 'coordinator')->firstOrFail();
    $this->assertFalse($role->locked);
    $this->assertSame('Coordinator', $role->label);
    $this->assertSame(0, $role->permissions->count());
});

test('delete row action is hidden for admin role', function (): void {
    $adminRole = $this->project->roleFor(ProjectRole::Admin);

    Livewire::test(ListRoles::class)
        ->assertTableActionHidden('delete', $adminRole);
});

test('delete role with members shows error and keeps role', function (): void {
    $role = $this->project->roles()->create([
        'name' => 'coordinator',
        'guard_name' => 'web',
        'locked' => false,
    ]);
    $member = User::factory()->create();
    $this->project->users()->attach($member, ['role_id' => $role->id]);

    Livewire::test(ListRoles::class)
        ->callTableAction('delete', $role)
        ->assertNotified(__('settings.roles.notifications.delete_has_members'));

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('delete removes empty custom role', function (): void {
    $role = $this->project->roles()->create([
        'name' => 'coordinator',
        'guard_name' => 'web',
        'locked' => false,
    ]);

    Livewire::test(ListRoles::class)
        ->callTableAction('delete', $role)
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});
