<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Settings\Resources\Roles\Pages\EditRole;
use App\Filament\Organization\Settings\Resources\Roles\Pages\ListRoles;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class RolesPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->admin->joinOrganization($this->organization, OrganizationRole::Admin);

        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_admin_can_load_list_page(): void
    {
        Livewire::test(ListRoles::class)->assertSuccessful();
    }

    public function test_non_admin_cannot_load_list_page(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->actingAs($member);

        Livewire::test(ListRoles::class)->assertForbidden();
    }

    public function test_admin_and_member_roles_are_seeded(): void
    {
        $this->assertNotNull($this->organization->roleFor(OrganizationRole::Admin));
        $this->assertNotNull($this->organization->roleFor(OrganizationRole::Member));
        $this->assertTrue($this->organization->roleFor(OrganizationRole::Admin)->locked);
        $this->assertFalse($this->organization->roleFor(OrganizationRole::Member)->locked);
    }

    public function test_seeded_member_role_has_view_permissions(): void
    {
        $memberRole = $this->organization->roleFor(OrganizationRole::Member);

        $this->assertTrue($memberRole->hasPermissionTo('view_any_project'));
        $this->assertTrue($memberRole->hasPermissionTo('view_project'));
        $this->assertTrue($memberRole->hasPermissionTo('view_any_member'));
        $this->assertTrue($memberRole->hasPermissionTo('view_member'));
        $this->assertFalse($memberRole->hasPermissionTo('create_project'));
        $this->assertFalse($memberRole->hasPermissionTo('delete_project'));
    }

    public function test_built_in_admin_role_is_listed(): void
    {
        Livewire::test(ListRoles::class)
            ->assertCanSeeTableRecords(
                $this->organization->roles()->get()->all(),
            );
    }

    public function test_create_role_modal_persists_role_and_redirects_to_edit(): void
    {
        Livewire::test(ListRoles::class)
            ->callAction('create', data: ['name' => 'coordinator'])
            ->assertHasNoActionErrors()
            ->assertRedirect();

        $role = $this->organization->roles()->where('name', 'coordinator')->firstOrFail();
        $this->assertFalse($role->locked);
        $this->assertSame('Coordinator', $role->label);
        $this->assertSame(0, $role->permissions->count());
    }

    public function test_delete_row_action_is_hidden_for_admin_role(): void
    {
        $adminRole = $this->organization->roleFor(OrganizationRole::Admin);

        Livewire::test(ListRoles::class)
            ->assertTableActionHidden('delete', $adminRole);
    }

    public function test_delete_role_with_members_shows_error_and_keeps_role(): void
    {
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
    }

    public function test_delete_removes_empty_custom_role(): void
    {
        $role = $this->organization->roles()->create([
            'name' => 'coordinator',
            'guard_name' => 'web',
            'locked' => false,
        ]);

        Livewire::test(ListRoles::class)
            ->callTableAction('delete', $role)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_edit_role_saves_label_and_permissions(): void
    {
        $role = $this->organization->roles()->create([
            'name' => 'coordinator',
            'guard_name' => 'web',
            'locked' => false,
        ]);

        Livewire::test(EditRole::class, ['record' => $role->id])
            ->fillForm([
                'label' => 'Coordinator EU',
                'permissions_project' => ['view_any_project', 'view_project'],
            ])
            ->call('save');

        $role->refresh();
        $this->assertSame('Coordinator EU', $role->label);
        $this->assertTrue($role->hasPermissionTo('view_any_project'));
        $this->assertTrue($role->hasPermissionTo('view_project'));
        $this->assertFalse($role->hasPermissionTo('delete_project'));
    }

    public function test_edit_role_replaces_previous_permissions(): void
    {
        $role = $this->organization->roles()->create([
            'name' => 'coordinator',
            'guard_name' => 'web',
            'locked' => false,
        ]);
        $role->syncPermissions(['view_any_project', 'create_project']);

        Livewire::test(EditRole::class, ['record' => $role->id])
            ->fillForm([
                'label' => 'Coordinator',
                'permissions_project' => ['view_project'],
            ])
            ->call('save');

        $role->refresh();
        $this->assertSame(['view_project'], $role->permissions->pluck('name')->all());
    }
}
