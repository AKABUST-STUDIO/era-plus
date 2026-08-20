<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use LivewireUI\Spotlight\Spotlight;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;

beforeEach(function (): void {
    $this->host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);

    Spotlight::$commands = [];
});

function spotlightUrl(string $host, string $path): string
{
    return 'http://'.$host.$path;
}

function spotlightMember(): User
{
    $user = User::factory()->create();
    $user->joinOrganization(Organization::factory()->create(), OrganizationRole::Admin);

    return $user;
}

/**
 * @return array<int, string>
 */
function spotlightCommandNames(): array
{
    return array_map(
        fn ($command): string => $command->getName(),
        array_values(Spotlight::$commands),
    );
}

test('spotlight plugin is registered on every panel', function (): void {
    $panels = [
        'organization',
        'project',
        'organization.settings',
        'user',
    ];

    foreach ($panels as $panel) {
        $this->assertInstanceOf(
            SpotlightPlugin::class,
            Filament::getPanel($panel)->getPlugin(SpotlightPlugin::$name),
            "Spotlight is not registered on the [{$panel}] panel.",
        );
    }
});

test('organization panel renders the spotlight component', function (): void {
    $user = spotlightMember();
    $organization = $user->organizations()->sole();

    $this->actingAs($user)
        ->get(spotlightUrl($this->host, '/'.$organization->slug.'/projects'))
        ->assertSuccessful()
        ->assertSee('livewire-ui-spotlight');

    $this->assertNotEmpty(Spotlight::$commands);
});

test('project panel renders the spotlight component', function (): void {
    $user = spotlightMember();
    $organization = $user->organizations()->sole();
    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project);

    $this->actingAs($user)
        ->get(spotlightUrl($this->host, '/'.$organization->slug.'/'.$project->slug.'/overview'))
        ->assertSuccessful()
        ->assertSee('livewire-ui-spotlight');

    $this->assertNotEmpty(Spotlight::$commands);
});

test('settings panel renders the spotlight component', function (): void {
    $user = spotlightMember();
    $organization = $user->organizations()->sole();

    $this->actingAs($user)
        ->get(spotlightUrl($this->host, '/'.$organization->slug.'/settings/overview'))
        ->assertSuccessful()
        ->assertSee('livewire-ui-spotlight');

    $this->assertNotEmpty(Spotlight::$commands);
});

test('user panel renders the spotlight component', function (): void {
    $this->actingAs(spotlightMember())
        ->get(spotlightUrl($this->host, '/profile/settings'))
        ->assertSuccessful()
        ->assertSee('livewire-ui-spotlight');

    $this->assertNotEmpty(Spotlight::$commands);
});

test('commands do not leak between panels', function (): void {
    $user = spotlightMember();
    $organization = $user->organizations()->sole();
    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project);

    $this->actingAs($user)
        ->get(spotlightUrl($this->host, '/'.$organization->slug.'/'.$project->slug.'/overview'))
        ->assertSuccessful();

    $projectCommands = spotlightCommandNames();
    $this->assertNotEmpty($projectCommands);

    $this->actingAs($user)
        ->get(spotlightUrl($this->host, '/'.$organization->slug.'/settings/overview'))
        ->assertSuccessful();

    $this->assertNotEmpty(array_diff($projectCommands, spotlightCommandNames()));
});
