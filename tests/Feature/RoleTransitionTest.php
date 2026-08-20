<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class RoleTransitionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->project = Project::factory()->for($this->organization)->create();

        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_demoting_admin_to_participant_revokes_admin_permissions(): void
    {
        $user = User::factory()->create();
        $user->joinOrganization($this->organization, OrganizationRole::Member);
        $user->joinProject($this->project, ProjectRole::Admin);

        $access = app(ProjectAccess::class);
        $this->assertTrue($access->can($user, 'create_project_user', $this->project));

        $user->joinProject($this->project, ProjectRole::Participant);

        $this->assertFalse($access->can($user->fresh(), 'create_project_user', $this->project));
    }

    public function test_promoting_participant_to_admin_grants_admin_permissions(): void
    {
        $user = User::factory()->create();
        $user->joinOrganization($this->organization, OrganizationRole::Member);
        $user->joinProject($this->project, ProjectRole::Participant);

        $access = app(ProjectAccess::class);
        $this->assertFalse($access->can($user, 'create_project_user', $this->project));

        $user->joinProject($this->project, ProjectRole::Admin);

        $this->assertTrue($access->can($user->fresh(), 'create_project_user', $this->project));
    }

    public function test_removing_from_project_revokes_all_project_access(): void
    {
        $user = User::factory()->create();
        $user->joinOrganization($this->organization, OrganizationRole::Member);
        $user->joinProject($this->project, ProjectRole::Participant);

        $this->assertTrue($user->canAccessTenant($this->project));

        ProjectUser::query()
            ->where('project_id', $this->project->id)
            ->where('user_id', $user->id)
            ->delete();

        $this->assertFalse($user->fresh()->canAccessTenant($this->project));
    }

    public function test_removing_from_project_makes_deep_urls_return_forbidden(): void
    {
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
    }

    public function test_removing_from_org_admin_no_longer_manages_projects(): void
    {
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
    }

    public function test_demoted_admin_livewire_page_hides_actions(): void
    {
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
    }

    public function test_org_admin_demoted_to_member_loses_project_edit_gate(): void
    {
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
    }
}
