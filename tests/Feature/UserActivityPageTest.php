<?php

namespace Tests\Feature;

use App\Filament\User\Pages\Activity;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserActivityPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    public function test_organizations_list_only_includes_user_memberships(): void
    {
        $mine = Organization::factory()->create(['name' => 'Mine']);
        $mine->users()->attach($this->user);

        Organization::factory()->create(['name' => 'Stranger']);

        $orgs = Livewire::test(Activity::class)
            ->instance()
            ->getOrganizations();

        $this->assertCount(1, $orgs);
        $this->assertSame('Mine', $orgs->first()->name);
    }

    public function test_projects_list_only_includes_user_memberships(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user);

        $mine = Project::factory()->for($org)->create(['name' => 'Mine project']);
        $mine->users()->attach($this->user);

        Project::factory()->for($org)->create(['name' => 'Stranger project']);

        $projects = Livewire::test(Activity::class)
            ->instance()
            ->getProjects();

        $this->assertCount(1, $projects);
        $this->assertSame('Mine project', $projects->first()->name);
    }

    public function test_org_activity_url_uses_settings_panel_route(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user);

        $url = (new Activity)->organizationActivityUrl($org);

        $this->assertNotNull($url);
        $this->assertStringContainsString('/settings/activity', $url);
        $this->assertStringContainsString($org->slug, $url);
    }

    public function test_project_activity_url_uses_project_panel_route(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user);

        $project = Project::factory()->for($org)->create();
        $project->users()->attach($this->user);

        $url = (new Activity)->projectActivityUrl($project);

        $this->assertNotNull($url);
        $this->assertStringContainsString('/project-activity-log', $url);
        $this->assertStringContainsString($project->slug, $url);
    }

    public function test_activity_page_renders_section_when_user_has_orgs(): void
    {
        $org = Organization::factory()->create(['name' => 'Renderable Org']);
        $org->users()->attach($this->user);

        Livewire::test(Activity::class)
            ->assertSee('Renderable Org')
            ->assertSee(__('user.activity.organizations.heading'));
    }
}
