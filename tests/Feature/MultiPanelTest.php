<?php

namespace Tests\Feature;

use App\Livewire\OrganizationMenu;
use App\Livewire\ProjectMenu;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiPanelTest extends TestCase
{
    use RefreshDatabase;

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);
    }

    private function url(string $path): string
    {
        return 'http://'.$this->host.$path;
    }

    public function test_organization_panel_serves_dashboard_to_member(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug))
            ->assertSuccessful();
    }

    public function test_organization_panel_404s_for_unknown_slug(): void
    {
        $user = User::factory()->create();
        Organization::factory()->create()->users()->attach($user);

        $this->actingAs($user)
            ->get($this->url('/no-such-org'))
            ->assertNotFound();
    }

    public function test_organization_panel_404s_when_user_is_not_a_member(): void
    {
        $member = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($member);

        $intruder = User::factory()->create();
        $intruderOrg = Organization::factory()->create();
        $intruderOrg->users()->attach($intruder);

        $this->actingAs($intruder)
            ->get($this->url('/'.$organization->slug))
            ->assertNotFound();
    }

    public function test_project_panel_serves_dashboard_to_member(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);
        $project = Project::factory()->for($organization)->create();
        $project->users()->attach($user);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/'.$project->slug))
            ->assertSuccessful();
    }

    public function test_settings_panel_serves_general_settings_to_member(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/settings/general'))
            ->assertSuccessful();
    }

    public function test_settings_panel_serves_activity_page_to_member(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/settings/activity'))
            ->assertSuccessful()
            ->assertSee(__('settings.activity.title'));
    }

    public function test_project_panel_404s_when_org_slug_unknown(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);
        $project = Project::factory()->for($organization)->create();
        $project->users()->attach($user);

        $this->actingAs($user)
            ->get($this->url('/no-such-org/'.$project->slug))
            ->assertNotFound();
    }

    public function test_project_panel_404s_when_project_belongs_to_a_different_org(): void
    {
        $user = User::factory()->create();
        $ownOrg = Organization::factory()->create();
        $ownOrg->users()->attach($user);

        $otherOrg = Organization::factory()->create();
        $project = Project::factory()->for($otherOrg)->create();
        $project->users()->attach($user);

        $this->actingAs($user)
            ->get($this->url('/'.$ownOrg->slug.'/'.$project->slug))
            ->assertNotFound();
    }

    public function test_project_panel_404s_when_two_orgs_share_a_project_slug_but_user_only_in_one(): void
    {
        $user = User::factory()->create();
        $ownOrg = Organization::factory()->create();
        $ownOrg->users()->attach($user);
        $ownProject = Project::factory()->for($ownOrg)->create(['name' => 'Shared', 'slug' => 'shared']);
        $ownProject->users()->attach($user);

        $otherOrg = Organization::factory()->create();
        Project::factory()->for($otherOrg)->create(['name' => 'Shared', 'slug' => 'shared']);

        $this->actingAs($user)
            ->get($this->url('/'.$otherOrg->slug.'/shared'))
            ->assertNotFound();
    }

    public function test_project_panel_404s_when_user_is_not_a_project_member(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);
        $project = Project::factory()->for($organization)->create();

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/'.$project->slug))
            ->assertNotFound();
    }

    public function test_project_panel_redirects_unauthenticated_request_to_organization_login(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->for($organization)->create();

        $response = $this->get($this->url('/'.$organization->slug.'/'.$project->slug));

        $response->assertRedirect();
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    public function test_project_menu_lists_only_members_projects_in_current_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);

        $myProject = Project::factory()->for($organization)->create(['name' => 'Mine']);
        $myProject->users()->attach($user);

        Project::factory()->for($organization)->create(['name' => 'Not Mine']);

        $otherOrg = Organization::factory()->create();
        $otherOrgProject = Project::factory()->for($otherOrg)->create(['name' => 'Other Org Project']);
        $otherOrgProject->users()->attach($user);

        $this->actingAs($user);
        Filament::setTenant($organization);

        Livewire::test(ProjectMenu::class)
            ->assertSee('Mine')
            ->assertDontSee('Not Mine')
            ->assertDontSee('Other Org Project');
    }

    public function test_organization_menu_lists_user_orgs_with_current_first_label(): void
    {
        $user = User::factory()->create();

        $currentOrg = Organization::factory()->create(['name' => 'Current Org']);
        $currentOrg->users()->attach($user);

        $otherOrg = Organization::factory()->create(['name' => 'Other Org']);
        $otherOrg->users()->attach($user);

        $strangerOrg = Organization::factory()->create(['name' => 'Stranger Org']);

        $project = Project::factory()->for($currentOrg)->create();
        $project->users()->attach($user);

        Filament::setCurrentPanel(Filament::getPanel('project'));
        $this->actingAs($user);
        Filament::setTenant($project);

        Livewire::test(OrganizationMenu::class)
            ->assertSee('Current Org')
            ->assertSee('Other Org')
            ->assertDontSee('Stranger Org');
    }
}
