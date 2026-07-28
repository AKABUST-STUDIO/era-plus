<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use App\Providers\Filament\OrganizationPanelProvider;
use App\Providers\Filament\ProjectPanelProvider;
use App\Providers\Filament\UserPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LivewireUI\Spotlight\Spotlight;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use Tests\TestCase;

class SpotlightTest extends TestCase
{
    use RefreshDatabase;

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);

        Spotlight::$commands = [];
    }

    private function url(string $path): string
    {
        return 'http://'.$this->host.$path;
    }

    private function member(): User
    {
        $user = User::factory()->create();
        $user->joinOrganization(Organization::factory()->create(), OrganizationRole::Admin);

        return $user;
    }

    /**
     * @return array<int, string>
     */
    private function commandNames(): array
    {
        return array_map(
            fn ($command): string => $command->getName(),
            array_values(Spotlight::$commands),
        );
    }

    public function test_spotlight_plugin_is_registered_on_every_panel(): void
    {
        $panels = [
            OrganizationPanelProvider::PANEL_ID,
            ProjectPanelProvider::PANEL_ID,
            SettingsPanelProvider::PANEL_ID,
            UserPanelProvider::PANEL_ID,
        ];

        foreach ($panels as $panel) {
            $this->assertInstanceOf(
                SpotlightPlugin::class,
                Filament::getPanel($panel)->getPlugin(SpotlightPlugin::$name),
                "Spotlight is not registered on the [{$panel}] panel.",
            );
        }
    }

    public function test_organization_panel_renders_the_spotlight_component(): void
    {
        $user = $this->member();
        $organization = $user->organizations()->sole();

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/overview'))
            ->assertSuccessful()
            ->assertSee('livewire-ui-spotlight');

        $this->assertNotEmpty(Spotlight::$commands);
    }

    public function test_project_panel_renders_the_spotlight_component(): void
    {
        $user = $this->member();
        $organization = $user->organizations()->sole();
        $project = Project::factory()->for($organization)->create();
        $user->joinProject($project);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/'.$project->slug.'/overview'))
            ->assertSuccessful()
            ->assertSee('livewire-ui-spotlight');

        $this->assertNotEmpty(Spotlight::$commands);
    }

    public function test_settings_panel_renders_the_spotlight_component(): void
    {
        $user = $this->member();
        $organization = $user->organizations()->sole();

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/settings/overview'))
            ->assertSuccessful()
            ->assertSee('livewire-ui-spotlight');

        $this->assertNotEmpty(Spotlight::$commands);
    }

    public function test_user_panel_renders_the_spotlight_component(): void
    {
        $this->actingAs($this->member())
            ->get($this->url('/profile/settings'))
            ->assertSuccessful()
            ->assertSee('livewire-ui-spotlight');

        $this->assertNotEmpty(Spotlight::$commands);
    }

    public function test_commands_do_not_leak_between_panels(): void
    {
        $user = $this->member();
        $organization = $user->organizations()->sole();
        $project = Project::factory()->for($organization)->create();
        $user->joinProject($project);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/'.$project->slug.'/overview'))
            ->assertSuccessful();

        $projectCommands = $this->commandNames();
        $this->assertNotEmpty($projectCommands);

        $this->actingAs($user)
            ->get($this->url('/'.$organization->slug.'/settings/overview'))
            ->assertSuccessful();

        $this->assertNotEmpty(array_diff($projectCommands, $this->commandNames()));
    }
}
