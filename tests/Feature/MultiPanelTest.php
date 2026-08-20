<?php

use App\Facades\OrganizationService;
use App\Filament\Resources\Projects\ProjectResource;
use App\Livewire\OrganizationMenu;
use App\Livewire\ProjectMenu;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);
});

function multiPanelUrl(string $host, string $path): string
{
    return 'http://'.$host.$path;
}

test('organization panel root lands a member on the projects page', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/'.$organization->slug))
        ->assertRedirect(ProjectResource::getUrl('index', panel: 'organization', tenant: $organization));

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/'.$organization->slug.'/projects'))
        ->assertSuccessful()
        ->assertSee(__('navigation.projects'));
});

test('organization panel 404s for unknown slug', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization(Organization::factory()->create());

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/no-such-org'))
        ->assertNotFound();
});

test('organization panel 404s when user is not a member', function (): void {
    $member = User::factory()->create();
    $organization = Organization::factory()->create();
    $member->joinOrganization($organization);

    $intruder = User::factory()->create();
    $intruderOrg = Organization::factory()->create();
    $intruder->joinOrganization($intruderOrg);

    $this->actingAs($intruder)
        ->get(multiPanelUrl($this->host, '/'.$organization->slug))
        ->assertNotFound();
});

test('project panel serves dashboard to member', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);
    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project);

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/'.$organization->slug.'/'.$project->slug))
        ->assertSuccessful();
});

test('settings panel serves general settings to member', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/'.$organization->slug.'/settings/general'))
        ->assertSuccessful();
});

test('settings panel serves activity page to member', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/'.$organization->slug.'/settings/activity'))
        ->assertSuccessful()
        ->assertSee(__('settings.activity.title'));
});

test('project panel 404s when org slug unknown', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);
    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project);

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/no-such-org/'.$project->slug))
        ->assertNotFound();
});

test('project panel 404s when project belongs to a different org', function (): void {
    $user = User::factory()->create();
    $ownOrg = Organization::factory()->create();
    $user->joinOrganization($ownOrg);

    $otherOrg = Organization::factory()->create();
    $project = Project::factory()->for($otherOrg)->create();
    $user->joinProject($project);

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/'.$ownOrg->slug.'/'.$project->slug))
        ->assertNotFound();
});

test('project panel 404s when two orgs share a project slug but user only in one', function (): void {
    $user = User::factory()->create();
    $ownOrg = Organization::factory()->create();
    $user->joinOrganization($ownOrg);
    $ownProject = Project::factory()->for($ownOrg)->create(['name' => 'Shared', 'slug' => 'shared']);
    $user->joinProject($ownProject);

    $otherOrg = Organization::factory()->create();
    Project::factory()->for($otherOrg)->create(['name' => 'Shared', 'slug' => 'shared']);

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/'.$otherOrg->slug.'/shared'))
        ->assertNotFound();
});

test('project panel 404s when user is not a project member', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);
    $project = Project::factory()->for($organization)->create();

    $this->actingAs($user)
        ->get(multiPanelUrl($this->host, '/'.$organization->slug.'/'.$project->slug))
        ->assertNotFound();
});

test('project panel redirects unauthenticated request to organization login', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->for($organization)->create();

    $response = $this->get(multiPanelUrl($this->host, '/'.$organization->slug.'/'.$project->slug));

    $response->assertRedirect();
    $this->assertStringContainsString('/login', $response->headers->get('Location'));
});

test('project menu lists only members projects in current organization', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);

    $myProject = Project::factory()->for($organization)->create(['name' => 'Mine']);
    $user->joinProject($myProject);

    Project::factory()->for($organization)->create(['name' => 'Not Mine']);

    $otherOrg = Organization::factory()->create();
    $otherOrgProject = Project::factory()->for($otherOrg)->create(['name' => 'Other Org Project']);
    $user->joinProject($otherOrgProject);

    $this->actingAs($user);
    Filament::setTenant($organization);

    Livewire::test(ProjectMenu::class)
        ->assertSee('Mine')
        ->assertDontSee('Not Mine')
        ->assertDontSee('Other Org Project');
});

test('organization menu lists user orgs with current first label', function (): void {
    $user = User::factory()->create();

    $currentOrg = Organization::factory()->create(['name' => 'Current Org']);
    $user->joinOrganization($currentOrg);

    $otherOrg = Organization::factory()->create(['name' => 'Other Org']);
    $user->joinOrganization($otherOrg);

    $strangerOrg = Organization::factory()->create(['name' => 'Stranger Org']);

    $project = Project::factory()->for($currentOrg)->create();
    $user->joinProject($project);

    Filament::setCurrentPanel(Filament::getPanel('project'));
    $this->actingAs($user);
    Filament::setTenant($project);

    Livewire::test(OrganizationMenu::class)
        ->assertSee('Current Org')
        ->assertSee('Other Org')
        ->assertDontSee('Stranger Org');
});

test('organization menu trigger links to organization overview', function (): void {
    $user = User::factory()->create();

    $organization = Organization::factory()->create(['name' => 'Current Org']);
    $user->joinOrganization($organization);

    Filament::setCurrentPanel(Filament::getPanel('organization'));
    $this->actingAs($user);
    Filament::setTenant($organization);

    Livewire::test(OrganizationMenu::class)
        ->assertSeeHtml('href="'.OrganizationService::urlFor($organization).'"')
        ->assertSeeHtml('fi-tenant-menu-toggle');
});
