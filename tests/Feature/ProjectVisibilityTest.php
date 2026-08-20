<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Filament\Resources\Projects\ProjectResource;
use App\Livewire\ProjectMenu;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Project $projectA;

    private Project $projectB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->projectA = Project::factory()->for($this->organization)->create(['name' => 'Alpha Project']);
        $this->projectB = Project::factory()->for($this->organization)->create(['name' => 'Beta Project']);

        OrganizationService::remember($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    private function actAsInOrganizationPanel(User $user): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);
    }

    private function actAsInProjectPanel(User $user, Project $project): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($project);
        ProjectService::remember($project);
    }

    public function test_bare_org_member_has_no_projects_in_the_switcher(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->actAsInOrganizationPanel($member);

        $this->assertCount(0, app(\App\Services\ProjectService::class)->projectsFor($member, $this->organization));
    }

    public function test_bare_org_member_gets_no_project_tenants(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $tenants = $member->getTenants(Filament::getPanel('project'));

        $this->assertCount(0, $tenants);
    }

    public function test_bare_org_member_cannot_access_project_panel(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->assertFalse($member->canAccessPanel(Filament::getPanel('project')));
    }

    public function test_bare_org_member_cannot_view_a_project_they_are_not_on(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->actAsInOrganizationPanel($member);

        $this->assertFalse(Gate::forUser($member)->allows('view', $this->projectA));
        $this->assertFalse(Gate::forUser($member)->allows('view', $this->projectB));
    }

    public function test_bare_org_member_cannot_create_projects(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->actAsInOrganizationPanel($member);

        $this->assertFalse(ProjectResource::canCreate());
    }

    public function test_participant_sees_only_their_project_in_the_switcher(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization, OrganizationRole::Member);
        $participant->joinProject($this->projectA, ProjectRole::Participant);

        $projects = app(\App\Services\ProjectService::class)->projectsFor($participant, $this->organization);

        $this->assertCount(1, $projects);
        $this->assertTrue($projects->first()->is($this->projectA));
    }

    public function test_participant_can_access_only_their_project_as_tenant(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization, OrganizationRole::Member);
        $participant->joinProject($this->projectA, ProjectRole::Participant);

        $this->assertTrue($participant->canAccessTenant($this->projectA));
        $this->assertFalse($participant->canAccessTenant($this->projectB));
    }

    public function test_bare_org_member_cannot_access_any_project_as_tenant(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->assertFalse($member->canAccessTenant($this->projectA));
        $this->assertFalse($member->canAccessTenant($this->projectB));
    }

    public function test_participant_cannot_view_other_projects_in_the_same_org(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization, OrganizationRole::Member);
        $participant->joinProject($this->projectA, ProjectRole::Participant);

        $this->actAsInProjectPanel($participant, $this->projectA);

        $this->assertTrue(Gate::forUser($participant)->allows('view', $this->projectA));
        $this->assertFalse(Gate::forUser($participant)->allows('view', $this->projectB));
    }

    public function test_participant_cannot_create_projects(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization, OrganizationRole::Member);
        $participant->joinProject($this->projectA, ProjectRole::Participant);

        $this->actAsInProjectPanel($participant, $this->projectA);

        $this->assertFalse(ProjectResource::canCreate());
    }

    public function test_project_menu_hides_create_url_for_participant(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization, OrganizationRole::Member);
        $participant->joinProject($this->projectA, ProjectRole::Participant);

        $this->actAsInProjectPanel($participant, $this->projectA);

        $test = Livewire::test(ProjectMenu::class)->assertOk();

        $this->assertNull($test->viewData('createUrl'));
    }

    public function test_project_menu_hides_create_url_for_bare_org_member(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->actAsInOrganizationPanel($member);

        $test = Livewire::test(ProjectMenu::class)->assertOk();

        $this->assertNull($test->viewData('createUrl'));
    }

    public function test_project_menu_exposes_create_url_for_org_admin(): void
    {
        $freshOrganization = Organization::factory()->create();
        $admin = User::factory()->create();
        $admin->joinOrganization($freshOrganization, OrganizationRole::Admin);

        OrganizationService::remember($freshOrganization);
        URL::defaults(['organization' => $freshOrganization->slug]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($freshOrganization);

        $test = Livewire::test(ProjectMenu::class)->assertOk();

        $url = $test->viewData('createUrl');
        $this->assertIsString($url);
        $this->assertNotSame('', $url);
    }

    public function test_participant_project_switcher_does_not_leak_other_projects(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization, OrganizationRole::Member);
        $participant->joinProject($this->projectA, ProjectRole::Participant);

        $this->actAsInProjectPanel($participant, $this->projectA);

        Livewire::test(ProjectMenu::class)
            ->assertOk()
            ->assertSee($this->projectA->name)
            ->assertDontSee($this->projectB->name);
    }
}
