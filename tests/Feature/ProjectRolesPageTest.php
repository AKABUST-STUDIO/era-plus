<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Settings\Resources\Roles\Pages\ListRoles;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectRolesPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_admin_can_load_list_page(): void
    {
        Livewire::test(ListRoles::class)->assertSuccessful();
    }

    public function test_non_admin_cannot_load_list_page(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization, OrganizationRole::Member);
        $participant->joinProject($this->project, ProjectRole::Participant);

        $this->actingAs($participant);

        Livewire::test(ListRoles::class)->assertForbidden();
    }

    public function test_admin_and_participant_roles_are_seeded(): void
    {
        $this->assertNotNull($this->project->roleFor(ProjectRole::Admin));
        $this->assertNotNull($this->project->roleFor(ProjectRole::Participant));
        $this->assertTrue($this->project->roleFor(ProjectRole::Admin)->locked);
        $this->assertFalse($this->project->roleFor(ProjectRole::Participant)->locked);
    }

    public function test_built_in_admin_role_is_listed(): void
    {
        Livewire::test(ListRoles::class)
            ->assertCanSeeTableRecords(
                $this->project->roles()->get()->all(),
            );
    }

    public function test_create_role_modal_persists_role_and_redirects_to_edit(): void
    {
        Livewire::test(ListRoles::class)
            ->callAction('create', data: ['name' => 'coordinator'])
            ->assertHasNoActionErrors()
            ->assertRedirect();

        $role = $this->project->roles()->where('name', 'coordinator')->firstOrFail();
        $this->assertFalse($role->locked);
        $this->assertSame('Coordinator', $role->label);
        $this->assertSame(0, $role->permissions->count());
    }

    public function test_delete_row_action_is_hidden_for_admin_role(): void
    {
        $adminRole = $this->project->roleFor(ProjectRole::Admin);

        Livewire::test(ListRoles::class)
            ->assertTableActionHidden('delete', $adminRole);
    }

    public function test_delete_role_with_members_shows_error_and_keeps_role(): void
    {
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
    }

    public function test_delete_removes_empty_custom_role(): void
    {
        $role = $this->project->roles()->create([
            'name' => 'coordinator',
            'guard_name' => 'web',
            'locked' => false,
        ]);

        Livewire::test(ListRoles::class)
            ->callTableAction('delete', $role)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
