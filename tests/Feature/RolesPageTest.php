<?php

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Settings\Resources\Roles\Pages\EditRole;
use App\Filament\Organization\Settings\Resources\Roles\Pages\ListRoles;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->admin->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    Filament::setTenant($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);
});

test('admin can load list page', function (): void {
    Livewire::test(ListRoles::class)->assertSuccessful();
});

test('non admin cannot load list page', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    $this->actingAs($member);

    Livewire::test(ListRoles::class)->assertForbidden();
});

test('admin and member roles are seeded', function (): void {
    $this->assertNotNull($this->organization->roleFor(OrganizationRole::Admin));
    $this->assertNotNull($this->organization->roleFor(OrganizationRole::Member));
    $this->assertTrue($this->organization->roleFor(OrganizationRole::Admin)->locked);
    $this->assertFalse($this->organization->roleFor(OrganizationRole::Member)->locked);
});

test('seeded member role has view permissions', function (): void {
    $memberRole = $this->organization->roleFor(OrganizationRole::Member);

    $this->assertTrue($memberRole->hasPermissionTo('view_any_project'));
    $this->assertTrue($memberRole->hasPermissionTo('view_any_organization_user'));
    $this->assertTrue($memberRole->hasPermissionTo('view_organization_user'));
    $this->assertFalse($memberRole->hasPermissionTo('create_project'));
    $this->assertFalse($memberRole->hasPermissionTo('delete_any_project'));
});

test('built in admin role is listed', function (): void {
    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords(
            $this->organization->roles()->get()->all(),
        );
});

test('create role modal persists role and redirects to edit', function (): void {
    Livewire::test(ListRoles::class)
        ->callAction('create', data: ['name' => 'coordinator'])
        ->assertHasNoActionErrors()
        ->assertRedirect();

    $role = $this->organization->roles()->where('name', 'coordinator')->firstOrFail();
    $this->assertFalse($role->locked);
    $this->assertSame('Coordinator', $role->label);
    $this->assertSame(0, $role->permissions->count());
});

test('delete row action is hidden for admin role', function (): void {
    $adminRole = $this->organization->roleFor(OrganizationRole::Admin);

    Livewire::test(ListRoles::class)
        ->assertTableActionHidden('delete', $adminRole);
});

test('delete role with members shows error and keeps role', function (): void {
    $role = $this->organization->roles()->create([
        'name' => 'coordinator',
        'guard_name' => 'web',
        'locked' => false,
    ]);
    $member = User::factory()->create();
    $this->organization->users()->attach($member, ['role_id' => $role->id]);

    Livewire::test(ListRoles::class)
        ->callTableAction('delete', $role)
        ->assertNotified(__('settings.roles.notifications.delete_has_members'));

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('delete removes empty custom role', function (): void {
    $role = $this->organization->roles()->create([
        'name' => 'coordinator',
        'guard_name' => 'web',
        'locked' => false,
    ]);

    Livewire::test(ListRoles::class)
        ->callTableAction('delete', $role)
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('edit role saves label and permissions', function (): void {
    $role = $this->organization->roles()->create([
        'name' => 'coordinator',
        'guard_name' => 'web',
        'locked' => false,
    ]);

    Livewire::test(EditRole::class, ['record' => $role->id])
        ->fillForm([
            'label' => 'Coordinator EU',
            'permissions' => ['view_any_project', 'create_project'],
        ])
        ->call('save');

    $role->refresh();
    $this->assertSame('Coordinator EU', $role->label);
    $this->assertTrue($role->hasPermissionTo('view_any_project'));
    $this->assertTrue($role->hasPermissionTo('create_project'));
    $this->assertFalse($role->hasPermissionTo('delete_any_project'));
});

test('edit role replaces previous permissions', function (): void {
    $role = $this->organization->roles()->create([
        'name' => 'coordinator',
        'guard_name' => 'web',
        'locked' => false,
    ]);
    $role->syncPermissions(['view_any_project', 'create_project']);

    Livewire::test(EditRole::class, ['record' => $role->id])
        ->fillForm([
            'label' => 'Coordinator',
            'permissions' => ['view_any_project'],
        ])
        ->call('save');

    $role->refresh();
    $this->assertSame(['view_any_project'], $role->permissions->pluck('name')->all());
});
