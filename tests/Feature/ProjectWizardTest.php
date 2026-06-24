<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Filament\Organization\Resources\Projects\Pages\CreateProject;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Nnjeim\World\Models\Country;
use Tests\TestCase;

class ProjectWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Country $estonia;

    private Country $germany;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('countries')->insert([
            ['iso2' => 'EE', 'iso3' => 'EST', 'name' => 'Estonia', 'status' => 1, 'phone_code' => '372', 'region' => 'Europe', 'subregion' => 'Northern Europe'],
            ['iso2' => 'DE', 'iso3' => 'DEU', 'name' => 'Germany', 'status' => 1, 'phone_code' => '49', 'region' => 'Europe', 'subregion' => 'Western Europe'],
        ]);

        $this->estonia = Country::query()->where('iso2', 'EE')->firstOrFail();
        $this->germany = Country::query()->where('iso2', 'DE')->firstOrFail();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->user, ['role' => \App\Enums\OrganizationRole::Admin->value, 'is_admin' => true]);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_wizard_renders(): void
    {
        Livewire::test(CreateProject::class)->assertSuccessful();
    }

    public function test_wizard_creates_project_with_countries(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Mobility 2026',
                'beginning_date' => '2026-09-01',
                'end_date' => '2027-06-30',
                'project_type' => 'mobility',
                'description' => 'Six month exchange',
                'project_countries' => [
                    ['country_id' => $this->estonia->id, 'default_travel_expense_limit' => 350.00],
                    ['country_id' => $this->germany->id, 'default_travel_expense_limit' => 600.00],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('name', 'Mobility 2026')->firstOrFail();

        $this->assertSame('mobility', $project->project_type);
        $this->assertSame('2026-09-01', $project->beginning_date->toDateString());
        $this->assertSame('2027-06-30', $project->end_date->toDateString());
        $this->assertSame('Six month exchange', $project->description);

        $this->assertDatabaseHas('project_countries', [
            'project_id' => $project->id,
            'country_id' => $this->estonia->id,
            'default_travel_expense_limit' => '350.00',
        ]);
        $this->assertDatabaseHas('project_countries', [
            'project_id' => $project->id,
            'country_id' => $this->germany->id,
            'default_travel_expense_limit' => '600.00',
        ]);
    }

    public function test_creator_attached_as_coordinator_via_wizard(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm(['name' => 'Coordinator Test'])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('name', 'Coordinator Test')->firstOrFail();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'role' => ProjectRole::Coordinator->value,
        ]);
    }

    public function test_end_date_must_be_after_beginning_date(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Bad Dates',
                'beginning_date' => '2026-09-01',
                'end_date' => '2026-08-01',
            ])
            ->call('create')
            ->assertHasFormErrors(['end_date']);
    }
}
