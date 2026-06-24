<?php

namespace Tests\Feature;

use App\Filament\Project\Widgets\ProjectInfoOverview;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_widgets_for_project_member(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);
        $project = Project::factory()->for($organization)->create(['name' => 'Mobility 2026']);
        $project->users()->attach($user);

        $host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);

        $this->actingAs($user)
            ->get('http://'.$host.'/'.$organization->slug.'/'.$project->slug)
            ->assertSuccessful()
            ->assertSee('Mobility 2026');
    }

    public function test_project_info_widget_shows_project_summary(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Acme Org']);
        $organization->users()->attach($user);
        $project = Project::factory()->for($organization)->create(['name' => 'Erasmus Pilot']);
        $project->users()->attach($user);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($project);
        URL::defaults(['organization' => $organization->slug]);

        Livewire::test(ProjectInfoOverview::class)
            ->assertSee('Erasmus Pilot')
            ->assertSee('Acme Org')
            ->assertSee('1');
    }
}
