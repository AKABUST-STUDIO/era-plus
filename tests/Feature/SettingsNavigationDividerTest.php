<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsNavigationDividerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->user->joinProject($this->project);

        $this->actingAs($this->user);
    }

    private function lastSidebarItem(string $path): string
    {
        $response = $this->followingRedirects()
            ->get('http://app.'.parse_url(config('app.url'), PHP_URL_HOST).$path);

        $response->assertSuccessful();

        preg_match_all('/<li[^>]*fi-sidebar-item[^>]*>/', (string) $response->getContent(), $matches);

        $this->assertNotEmpty($matches[0]);

        return (string) end($matches[0]);
    }

    public function test_organization_panel_divides_settings_from_the_pages_above_it(): void
    {
        $item = $this->lastSidebarItem('/'.$this->organization->slug);

        $this->assertStringContainsString('border-t', $item);
        $this->assertStringContainsString('dark:border-white/10', $item);
    }

    public function test_project_panel_divides_settings_from_the_pages_above_it(): void
    {
        $item = $this->lastSidebarItem('/'.$this->organization->slug.'/'.$this->project->slug);

        $this->assertStringContainsString('border-t', $item);
        $this->assertStringContainsString('dark:border-white/10', $item);
    }
}
