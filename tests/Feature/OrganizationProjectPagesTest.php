<?php

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
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);

    $this->app->instance(CalendarClient::class, new FakeCalendarClient);
});

function tenantUrl(string $path, string $host): string
{
    return parse_url(config('app.url'), PHP_URL_SCHEME).'://'.$host.$path;
}

function orgMember(): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($organization);
    OrganizationService::remember($organization);
    URL::defaults(['organization' => $organization->slug]);

    return [$user, $organization];
}

dataset('placeholder_slugs', [
    'participants' => ['participants'],
    'finance' => ['finance'],
    'events' => ['events'],
]);

test('organization panel serves the placeholder page', function (string $slug): void {
    [, $organization] = orgMember();

    $this->get(tenantUrl('/'.$organization->slug.'/'.$slug, $this->host))
        ->assertSuccessful()
        ->assertSee(__('organization.select_project.heading'));
})->with('placeholder_slugs');

test('placeholder project menu links to the same page in the project panel', function (string $slug): void {
    [$user, $organization] = orgMember();

    $project = Project::factory()->for($organization)->create(['name' => 'Mine']);
    $user->joinProject($project, ProjectRole::Admin);

    $this->get(tenantUrl('/'.$organization->slug.'/'.$slug, $this->host))
        ->assertSuccessful()
        ->assertSee(tenantUrl('/'.$organization->slug.'/'.$project->slug.'/'.$slug, $this->host), escape: false);
})->with('placeholder_slugs');

test('project menu keeps the current page when switching project', function (string $slug): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);

    $current = Project::factory()->for($organization)->create(['name' => 'Current']);
    $other = Project::factory()->for($organization)->create(['name' => 'Other']);
    $user->joinProject($current, ProjectRole::Admin);
    $user->joinProject($other, ProjectRole::Admin);

    $this->actingAs($user)
        ->get(tenantUrl('/'.$organization->slug.'/'.$current->slug.'/'.$slug, $this->host))
        ->assertSuccessful()
        ->assertSee(tenantUrl('/'.$organization->slug.'/'.$other->slug.'/'.$slug, $this->host), escape: false);
})->with('placeholder_slugs');

test('organization menu keeps the current page when switching organization', function (string $slug): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $other = Organization::factory()->create();
    $user->joinOrganization($organization);
    $user->joinOrganization($other);

    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project, ProjectRole::Admin);

    $this->actingAs($user)
        ->get(tenantUrl('/'.$organization->slug.'/'.$project->slug.'/'.$slug, $this->host))
        ->assertSuccessful()
        ->assertSee(tenantUrl('/'.$other->slug.'/'.$slug, $this->host), escape: false);
})->with('placeholder_slugs');

test('project menu falls back to overview for pages the project panel does not have', function (): void {
    [$user, $organization] = orgMember();

    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project, ProjectRole::Admin);

    $this->get(tenantUrl('/'.$organization->slug.'/projects', $this->host))
        ->assertSuccessful()
        ->assertSee(tenantUrl('/'.$organization->slug.'/'.$project->slug.'/overview', $this->host), escape: false)
        ->assertDontSee(tenantUrl('/'.$organization->slug.'/'.$project->slug.'/projects', $this->host), escape: false);
});

test('users page switches between the organization and project panels', function (): void {
    [$user, $organization] = orgMember();

    $project = Project::factory()->for($organization)->create(['name' => 'Alpha']);
    $user->joinProject($project, ProjectRole::Admin);

    $this->get(tenantUrl('/'.$organization->slug.'/users', $this->host))
        ->assertSuccessful()
        ->assertSee(tenantUrl('/'.$organization->slug.'/'.$project->slug.'/users', $this->host), escape: false);

    $this->get(tenantUrl('/'.$organization->slug.'/'.$project->slug.'/users', $this->host))
        ->assertSuccessful()
        ->assertSee(tenantUrl('/'.$organization->slug.'/users', $this->host), escape: false);
});

test('organization menu falls back to the landing page for pages the organization panel does not have', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $other = Organization::factory()->create(['name' => 'Other Org']);
    $user->joinOrganization($organization);
    $user->joinOrganization($other);

    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project, ProjectRole::Admin);

    $this->actingAs($user)
        ->get(tenantUrl('/'.$organization->slug.'/'.$project->slug.'/overview', $this->host))
        ->assertSuccessful()
        ->assertSee(tenantUrl('/'.$other->slug.'/projects', $this->host), escape: false);
});

test('placeholder project menu only offers projects of the current organization', function (): void {
    [$user, $organization] = orgMember();

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
});

test('menus link to the landing pages outside of a recognised panel page', function (): void {
    [$user, $organization] = orgMember();

    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project, ProjectRole::Admin);

    Livewire::test(ProjectMenu::class)
        ->assertSee(tenantUrl('/'.$organization->slug.'/'.$project->slug.'/overview', $this->host), escape: false);

    Livewire::test(OrganizationMenu::class)
        ->assertSee(tenantUrl('/'.$organization->slug.'/projects', $this->host), escape: false);
});
