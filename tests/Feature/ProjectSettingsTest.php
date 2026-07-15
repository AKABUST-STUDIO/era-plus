<?php

namespace Tests\Feature;

use App\Filament\Project\Pages\ProjectSettings;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
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
        $this->user->joinOrganization($this->organization);
        $this->project = Project::factory()->for($this->organization)->create([
            'name' => 'Original',
            'beginning_date' => '2026-09-01',
        ]);
        $this->user->joinProject($this->project);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_settings_page_renders(): void
    {
        Livewire::test(ProjectSettings::class)->assertSuccessful();
    }

    public function test_can_save_details(): void
    {
        Livewire::test(ProjectSettings::class)
            ->fillForm([
                'name' => 'Renamed',
                'slug' => 'renamed',
                'project_type' => 'cooperation',
                'description' => 'Updated description',
            ])
            ->call('saveDetails');

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'name' => 'Renamed',
            'slug' => 'renamed',
            'project_type' => 'cooperation',
            'description' => 'Updated description',
        ]);
    }

    public function test_can_save_dates(): void
    {
        Livewire::test(ProjectSettings::class)
            ->fillForm([
                'beginning_date' => '2026-10-01',
                'end_date' => '2027-03-31',
            ])
            ->call('saveDates');

        $project = $this->project->fresh();

        $this->assertSame('2026-10-01', $project->beginning_date->toDateString());
        $this->assertSame('2027-03-31', $project->end_date->toDateString());
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        Livewire::test(ProjectSettings::class)
            ->fillForm([
                'beginning_date' => '2026-09-01',
                'end_date' => '2026-08-01',
            ])
            ->call('saveDates')
            ->assertHasFormErrors(['end_date']);
    }

    public function test_can_delete_project(): void
    {
        Livewire::test(ProjectSettings::class)
            ->call('deleteProject');

        $this->assertSoftDeleted ?? null;
        $this->assertDatabaseMissing('projects', ['id' => $this->project->id]);
    }
}
