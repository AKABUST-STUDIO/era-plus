<?php

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Organization\Settings\Pages\Billing;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Filament\Organization\Settings\Resources\Roles\RoleResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

/**
 * @return array{0: User, 1: Organization}
 */
function userWithRole(OrganizationRole $role): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization, $role);

    return [$user, $organization];
}

function actOnSettingsPanel(object $testCase, User $user, Organization $organization): void
{
    $testCase->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    Filament::setTenant($organization);
    OrganizationService::remember($organization);
    URL::defaults(['organization' => $organization->slug]);
}

function actOnOrganizationPanel(object $testCase, User $user, Organization $organization): void
{
    $testCase->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($organization);
}

test('admin can access the settings pages', function (): void {
    $this->markTestSkipped('Billing page is intentionally hidden.');

    [$user, $organization] = userWithRole(OrganizationRole::Admin);
    actOnSettingsPanel($this, $user, $organization);

    $this->assertTrue(OrganizationSettings::canAccess());
    $this->assertTrue(Billing::canAccess());
});

test('member can open organization settings but not billing', function (): void {
    [$user, $organization] = userWithRole(OrganizationRole::Member);
    actOnSettingsPanel($this, $user, $organization);

    $this->assertTrue(OrganizationSettings::canAccess());
    $this->assertFalse($user->can('update', $organization));
    $this->assertFalse(Billing::canAccess());
});

test('member gets forbidden when mounting a gated settings page', function (): void {
    [$user, $organization] = userWithRole(OrganizationRole::Member);
    actOnSettingsPanel($this, $user, $organization);

    Livewire::test(Billing::class)->assertForbidden();
});

test('role without permissions can only view the organization', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $empty = $organization->roles()->create([
        'name' => 'no-permissions',
        'guard_name' => 'web',
        'label' => 'No Permissions',
        'locked' => false,
    ]);

    $user->joinOrganization($organization, $empty);
    actOnSettingsPanel($this, $user, $organization);

    $this->assertTrue(OrganizationSettings::canAccess());
    $this->assertFalse($user->can('update', $organization));
    $this->assertFalse(OrganizationUserResource::canViewAny());
    $this->assertFalse(RoleResource::canViewAny());
});

test('member can access the users page', function (): void {
    [$user, $organization] = userWithRole(OrganizationRole::Member);
    actOnOrganizationPanel($this, $user, $organization);

    $this->assertTrue(OrganizationUserResource::canViewAny());
});

test('admin can view roles but member cannot', function (): void {
    [$admin, $organization] = userWithRole(OrganizationRole::Admin);
    actOnSettingsPanel($this, $admin, $organization);
    $this->assertTrue(RoleResource::canViewAny());

    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);
    actOnSettingsPanel($this, $member, $organization);
    $this->assertFalse(RoleResource::canViewAny());
});

test('locked roles cannot be updated or deleted', function (): void {
    [$admin, $organization] = userWithRole(OrganizationRole::Admin);
    actOnSettingsPanel($this, $admin, $organization);

    $adminRole = $organization->roleFor(OrganizationRole::Admin);
    $memberRole = $organization->roleFor(OrganizationRole::Member);

    $this->assertTrue($adminRole->locked);
    $this->assertFalse(Gate::forUser($admin)->allows('update', $adminRole));
    $this->assertFalse(Gate::forUser($admin)->allows('delete', $adminRole));
    $this->assertTrue(Gate::forUser($admin)->allows('update', $memberRole));
});

test('role policy denies a role from another organization', function (): void {
    [$admin, $organization] = userWithRole(OrganizationRole::Admin);
    actOnSettingsPanel($this, $admin, $organization);

    $foreignRole = Organization::factory()->create()->roleFor(OrganizationRole::Member);

    $this->assertInstanceOf(Role::class, $foreignRole);
    $this->assertFalse(Gate::forUser($admin)->allows('view', $foreignRole));
});

test('project policy follows organization permissions', function (): void {
    [$admin, $organization] = userWithRole(OrganizationRole::Admin);
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);

    actOnOrganizationPanel($this, $admin, $organization);
    $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Project::class));
    $this->assertTrue(Gate::forUser($admin)->allows('update', $project));
    $this->assertTrue(Gate::forUser($admin)->allows('delete', $project));

    actOnOrganizationPanel($this, $member, $organization);
    $this->assertTrue(Gate::forUser($member)->allows('viewAny', Project::class));
    $this->assertFalse(Gate::forUser($member)->allows('view', $project));
    $this->assertFalse(Gate::forUser($member)->allows('create', Project::class));
    $this->assertFalse(Gate::forUser($member)->allows('update', $project));
    $this->assertFalse(Gate::forUser($member)->allows('delete', $project));

    $member->joinProject($project);
    $this->assertTrue(Gate::forUser($member->fresh())->allows('view', $project));
});

test('outsider is denied every organization permission', function (): void {
    [, $organization] = userWithRole(OrganizationRole::Admin);
    $outsider = User::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($outsider);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($organization);

    $this->assertFalse(Gate::forUser($outsider)->allows('viewAny', Project::class));
    $this->assertFalse(Gate::forUser($outsider)->allows('view', $project));
    $this->assertFalse($outsider->canAccessTenant($organization));
});

test('settings panel is closed to users without an organization', function (): void {
    $user = User::factory()->create();

    $this->assertFalse($user->canAccessPanel(Filament::getPanel('organization.settings')));

    $user->joinOrganization(Organization::factory()->create(), OrganizationRole::Member);

    $this->assertTrue($user->fresh()->canAccessPanel(Filament::getPanel('organization.settings')));
});

test('custom role permissions are honoured', function (): void {
    [$user, $organization] = userWithRole(OrganizationRole::Member);

    $viewer = $organization->roles()->create([
        'name' => 'settings-viewer',
        'guard_name' => 'web',
        'label' => 'Settings Viewer',
        'locked' => false,
    ]);
    $viewer->syncPermissions(['view_any_project']);

    $user->joinOrganization($organization, $viewer);
    actOnSettingsPanel($this, $user->fresh(), $organization);

    $this->assertTrue(OrganizationSettings::canAccess());

    Livewire::test(OrganizationSettings::class)
        ->fillForm(['name' => 'Nope'])
        ->assertActionDoesNotExist(TestAction::make('saveName')->schemaComponent('name-section', 'form'));

    $this->assertNotSame('Nope', $organization->fresh()->name);
});
