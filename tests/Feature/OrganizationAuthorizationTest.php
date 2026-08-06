<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Pages\OrganizationUsers as UsersPage;
use App\Filament\Organization\Settings\Pages\Billing;
use App\Filament\Organization\Settings\Pages\GeneralSettings;
use App\Filament\Organization\Settings\Resources\Roles\RoleResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    private function userWithRole(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->joinOrganization($organization, $role);

        return [$user, $organization];
    }

    private function actOnSettingsPanel(User $user, Organization $organization): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
        Filament::setTenant($organization);
        OrganizationService::remember($organization);
        URL::defaults(['organization' => $organization->slug]);
    }

    private function actOnOrganizationPanel(User $user, Organization $organization): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);
    }

    public function test_admin_can_access_the_settings_pages(): void
    {
        [$user, $organization] = $this->userWithRole(OrganizationRole::Admin);
        $this->actOnSettingsPanel($user, $organization);

        $this->assertTrue(GeneralSettings::canAccess());
        $this->assertTrue(Billing::canAccess());
    }

    public function test_member_cannot_access_the_settings_pages(): void
    {
        [$user, $organization] = $this->userWithRole(OrganizationRole::Member);
        $this->actOnSettingsPanel($user, $organization);

        $this->assertFalse(GeneralSettings::canAccess());
        $this->assertFalse(Billing::canAccess());
    }

    public function test_member_gets_forbidden_when_mounting_a_settings_page(): void
    {
        [$user, $organization] = $this->userWithRole(OrganizationRole::Member);
        $this->actOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)->assertForbidden();
    }

    public function test_role_without_permissions_cannot_access_anything(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $empty = $organization->roles()->create([
            'name' => 'no-permissions',
            'guard_name' => 'web',
            'label' => 'No Permissions',
            'locked' => false,
        ]);

        $user->joinOrganization($organization, $empty);
        $this->actOnSettingsPanel($user, $organization);

        $this->assertFalse(GeneralSettings::canAccess());
        $this->assertFalse(UsersPage::canAccess());
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_member_can_access_the_users_page(): void
    {
        [$user, $organization] = $this->userWithRole(OrganizationRole::Member);
        $this->actOnOrganizationPanel($user, $organization);

        $this->assertTrue(UsersPage::canAccess());
    }

    public function test_admin_can_view_roles_but_member_cannot(): void
    {
        [$admin, $organization] = $this->userWithRole(OrganizationRole::Admin);
        $this->actOnSettingsPanel($admin, $organization);
        $this->assertTrue(RoleResource::canViewAny());

        $member = User::factory()->create();
        $member->joinOrganization($organization, OrganizationRole::Member);
        $this->actOnSettingsPanel($member, $organization);
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_locked_roles_cannot_be_updated_or_deleted(): void
    {
        [$admin, $organization] = $this->userWithRole(OrganizationRole::Admin);
        $this->actOnSettingsPanel($admin, $organization);

        $adminRole = $organization->roleFor(OrganizationRole::Admin);
        $memberRole = $organization->roleFor(OrganizationRole::Member);

        $this->assertTrue($adminRole->locked);
        $this->assertFalse(Gate::forUser($admin)->allows('update', $adminRole));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $adminRole));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $memberRole));
    }

    public function test_role_policy_denies_a_role_from_another_organization(): void
    {
        [$admin, $organization] = $this->userWithRole(OrganizationRole::Admin);
        $this->actOnSettingsPanel($admin, $organization);

        $foreignRole = Organization::factory()->create()->roleFor(OrganizationRole::Member);

        $this->assertInstanceOf(Role::class, $foreignRole);
        $this->assertFalse(Gate::forUser($admin)->allows('view', $foreignRole));
    }

    public function test_project_policy_follows_organization_permissions(): void
    {
        [$admin, $organization] = $this->userWithRole(OrganizationRole::Admin);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $member = User::factory()->create();
        $member->joinOrganization($organization, OrganizationRole::Member);

        $this->actOnOrganizationPanel($admin, $organization);
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Project::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $project));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $project));

        $this->actOnOrganizationPanel($member, $organization);
        $this->assertTrue(Gate::forUser($member)->allows('viewAny', Project::class));
        $this->assertTrue(Gate::forUser($member)->allows('view', $project));
        $this->assertFalse(Gate::forUser($member)->allows('create', Project::class));
        $this->assertFalse(Gate::forUser($member)->allows('update', $project));
        $this->assertFalse(Gate::forUser($member)->allows('delete', $project));
    }

    public function test_outsider_is_denied_every_organization_permission(): void
    {
        [, $organization] = $this->userWithRole(OrganizationRole::Admin);
        $outsider = User::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($outsider);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);

        $this->assertFalse(Gate::forUser($outsider)->allows('viewAny', Project::class));
        $this->assertFalse(Gate::forUser($outsider)->allows('view', $project));
        $this->assertFalse($outsider->canAccessTenant($organization));
    }

    public function test_settings_panel_is_closed_to_users_without_an_organization(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('organization.settings')));

        $user->joinOrganization(Organization::factory()->create(), OrganizationRole::Member);

        $this->assertTrue($user->fresh()->canAccessPanel(Filament::getPanel('organization.settings')));
    }

    public function test_custom_role_permissions_are_honoured(): void
    {
        [$user, $organization] = $this->userWithRole(OrganizationRole::Member);

        $viewer = $organization->roles()->create([
            'name' => 'settings-viewer',
            'guard_name' => 'web',
            'label' => 'Settings Viewer',
            'locked' => false,
        ]);
        $viewer->syncPermissions(['view_organization']);

        $user->joinOrganization($organization, $viewer);
        $this->actOnSettingsPanel($user->fresh(), $organization);

        $this->assertTrue(GeneralSettings::canAccess());

        Livewire::test(GeneralSettings::class)
            ->fillForm(['name' => 'Nope'])
            ->assertActionDoesNotExist(TestAction::make('saveName')->schemaComponent('name-section', 'form'));

        $this->assertNotSame('Nope', $organization->fresh()->name);
    }
}
