<?php

namespace Tests\Feature;

use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Livewire\OrganizationMenu;
use App\Livewire\ProjectMenu;
use App\Livewire\ProjectMenuInline;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\GoogleCalendar\Contracts\CalendarClient;
use App\Services\GoogleCalendar\FakeCalendarClient;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizationProjectPagesTest extends TestCase
{
    use RefreshDatabase;

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);

        $this->app->instance(CalendarClient::class, new FakeCalendarClient);
    }

    private function url(string $path): string
    {
        return parse_url(config('app.url'), PHP_URL_SCHEME).'://'.$this->host.$path;
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function memberOfOrganization(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->joinOrganization($organization);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);
        OrganizationService::remember($organization);
        URL::defaults(['organization' => $organization->slug]);

        return [$user, $organization];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function placeholderSlugs(): array
    {
        return [
            'participants' => ['participants'],
            'finance' => ['finance'],
            'events' => ['events'],
        ];
    }

    #[DataProvider('placeholderSlugs')]
    public function test_organization_panel_serves_the_placeholder_page(string $slug): void
    {
        [, $organization] = $this->memberOfOrganization();

        $this->get($this->url('/'.$organization->slug.'/'.$slug))
            ->assertSuccessful()
            ->assertSee(__('organization.select_project.heading'));
    }

    #[DataProvider('placeholderSlugs')]
    public function test_placeholder_project_menu_links_to_the_same_page_in_the_project_panel(string $slug): void
    {
        [$user, $organization] = $this->memberOfOrganization();

        $project = Project::factory()->for($organization)->create(['name' => 'Mine']);
        $user->joinProject($project, ProjectRole::Admin);

        $this->get($this->url('/'.$organization->slug.'/'.$slug))
            ->assertSuccessful()
            ->assertSee($this->url('/'.$organization->slug.'/'.$project->slug.'/'.$slug), escape: false);
    }

    #[DataProvider('placeholderSlugs')]
    public function test_project_menu_keeps_the_current_page_when_switching_project(string $slug): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->joinOrganization($organization);

        $current = Project::factory()->for($organization)->create(['name' => 'Current']);
        $other = Project::factory()->for($organization)->create(['name' => 'Other']);
        $user->joinProject($current, ProjectRole::Admin);
        $user->joinProject($other, ProjectRole::Admin);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/'.$current->slug.'/'.$slug))
            ->assertSuccessful()
            ->assertSee($this->url('/'.$organization->slug.'/'.$other->slug.'/'.$slug), escape: false);
    }

    #[DataProvider('placeholderSlugs')]
    public function test_organization_menu_keeps_the_current_page_when_switching_organization(string $slug): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $other = Organization::factory()->create();
        $user->joinOrganization($organization);
        $user->joinOrganization($other);

        $project = Project::factory()->for($organization)->create();
        $user->joinProject($project, ProjectRole::Admin);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/'.$project->slug.'/'.$slug))
            ->assertSuccessful()
            ->assertSee($this->url('/'.$other->slug.'/'.$slug), escape: false);
    }

    public function test_project_menu_falls_back_to_overview_for_pages_the_project_panel_does_not_have(): void
    {
        [$user, $organization] = $this->memberOfOrganization();

        $project = Project::factory()->for($organization)->create();
        $user->joinProject($project, ProjectRole::Admin);

        $this->get($this->url('/'.$organization->slug.'/projects'))
            ->assertSuccessful()
            ->assertSee($this->url('/'.$organization->slug.'/'.$project->slug.'/overview'), escape: false)
            ->assertDontSee($this->url('/'.$organization->slug.'/'.$project->slug.'/projects'), escape: false);
    }

    public function test_users_page_switches_between_the_organization_and_project_panels(): void
    {
        [$user, $organization] = $this->memberOfOrganization();

        $project = Project::factory()->for($organization)->create(['name' => 'Alpha']);
        $user->joinProject($project, ProjectRole::Admin);

        $this->get($this->url('/'.$organization->slug.'/users'))
            ->assertSuccessful()
            ->assertSee($this->url('/'.$organization->slug.'/'.$project->slug.'/users'), escape: false);

        $this->get($this->url('/'.$organization->slug.'/'.$project->slug.'/users'))
            ->assertSuccessful()
            ->assertSee($this->url('/'.$organization->slug.'/users'), escape: false);
    }

    public function test_organization_menu_falls_back_to_the_landing_page_for_pages_the_organization_panel_does_not_have(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $other = Organization::factory()->create(['name' => 'Other Org']);
        $user->joinOrganization($organization);
        $user->joinOrganization($other);

        $project = Project::factory()->for($organization)->create();
        $user->joinProject($project, ProjectRole::Admin);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/'.$project->slug.'/overview'))
            ->assertSuccessful()
            ->assertSee($this->url('/'.$other->slug.'/projects'), escape: false);
    }

    public function test_placeholder_project_menu_only_offers_projects_of_the_current_organization(): void
    {
        [$user, $organization] = $this->memberOfOrganization();

        $mine = Project::factory()->for($organization)->create(['name' => 'Mine']);
        $user->joinProject($mine, ProjectRole::Admin);

        Project::factory()->for($organization)->create(['name' => 'Not Mine']);

        $otherOrganization = Organization::factory()->create();
        $otherProject = Project::factory()->for($otherOrganization)->create(['name' => 'Other Org Project']);
        $user->joinProject($otherProject, ProjectRole::Admin);

        Livewire::test(ProjectMenuInline::class)
            ->assertSee('Mine')
            ->assertDontSee('Not Mine')
            ->assertDontSee('Other Org Project');
    }

    public function test_menus_link_to_the_landing_pages_outside_of_a_recognised_panel_page(): void
    {
        [$user, $organization] = $this->memberOfOrganization();

        $project = Project::factory()->for($organization)->create();
        $user->joinProject($project, ProjectRole::Admin);

        Livewire::test(ProjectMenu::class)
            ->assertSee($this->url('/'.$organization->slug.'/'.$project->slug.'/overview'), escape: false);

        Livewire::test(OrganizationMenu::class)
            ->assertSee($this->url('/'.$organization->slug.'/projects'), escape: false);
    }
}
