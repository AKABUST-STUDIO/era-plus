<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
    $this->project = Project::factory()->for($this->organization)->create();
    $this->user->joinProject($this->project);

    $this->actingAs($this->user);
});

function settingsNavigationDividerLastSidebarItem($test, string $path): string
{
    $response = $test->followingRedirects()
        ->get('http://app.'.parse_url(config('app.url'), PHP_URL_HOST).$path);

    $response->assertSuccessful();

    preg_match_all('/<li[^>]*fi-sidebar-item[^>]*>/', (string) $response->getContent(), $matches);

    $test->assertNotEmpty($matches[0]);

    return (string) end($matches[0]);
}

test('organization panel divides settings from the pages above it', function (): void {
    $item = settingsNavigationDividerLastSidebarItem($this, '/'.$this->organization->slug);

    $this->assertStringContainsString('border-t', $item);
    $this->assertStringContainsString('dark:border-white/10', $item);
});

test('project panel divides settings from the pages above it', function (): void {
    $item = settingsNavigationDividerLastSidebarItem($this, '/'.$this->organization->slug.'/'.$this->project->slug);

    $this->assertStringContainsString('border-t', $item);
    $this->assertStringContainsString('dark:border-white/10', $item);
});
