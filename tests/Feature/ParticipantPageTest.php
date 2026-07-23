<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\Participants\Pages\CreateParticipant;
use App\Filament\Project\Resources\Participants\Pages\EditParticipant;
use App\Filament\Project\Resources\Participants\Pages\ListParticipants;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectCountry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Nnjeim\World\Models\Country;
use Tests\TestCase;

class ParticipantPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    private Country $estonia;

    private Country $germany;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('countries')->insert([
            ['iso2' => 'EE', 'iso3' => 'EST', 'name' => 'Estonia', 'status' => 1, 'phone_code' => '372', 'region' => 'Europe', 'subregion' => 'Northern Europe'],
            ['iso2' => 'DE', 'iso3' => 'DEU', 'name' => 'Germany', 'status' => 1, 'phone_code' => '49', 'region' => 'Europe', 'subregion' => 'Western Europe'],
            ['iso2' => 'FR', 'iso3' => 'FRA', 'name' => 'France', 'status' => 1, 'phone_code' => '33', 'region' => 'Europe', 'subregion' => 'Western Europe'],
        ]);

        $this->estonia = Country::query()->where('iso2', 'EE')->firstOrFail();
        $this->germany = Country::query()->where('iso2', 'DE')->firstOrFail();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->user->joinProject($this->project, ProjectRole::Admin);

        ProjectCountry::create(['project_id' => $this->project->id, 'country_id' => $this->estonia->id]);
        ProjectCountry::create(['project_id' => $this->project->id, 'country_id' => $this->germany->id]);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_list_page_loads(): void
    {
        Livewire::test(ListParticipants::class)
            ->assertSuccessful();
    }

    public function test_list_page_shows_participants_and_countries(): void
    {
        Participant::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'country_id' => $this->estonia->id,
            'first_name' => 'Anne',
            'last_name' => 'Tamm',
        ]);

        Livewire::test(ListParticipants::class)
            ->assertSee('Anne')
            ->assertSee('Tamm')
            ->assertSee('Estonia');
    }

    public function test_participant_can_be_created_for_project_country(): void
    {
        Livewire::test(CreateParticipant::class)
            ->fillForm([
                'country_id' => $this->germany->id,
                'first_name' => 'Hans',
                'last_name' => 'Meier',
                'email' => 'hans@example.com',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('participants', [
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'country_id' => $this->germany->id,
            'first_name' => 'Hans',
            'last_name' => 'Meier',
            'email' => 'hans@example.com',
        ]);
    }

    public function test_create_requires_country_first_last_name(): void
    {
        Livewire::test(CreateParticipant::class)
            ->fillForm([
                'country_id' => null,
                'first_name' => null,
                'last_name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['country_id', 'first_name', 'last_name']);
    }

    public function test_participant_can_be_edited(): void
    {
        $participant = Participant::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'country_id' => $this->estonia->id,
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        Livewire::test(EditParticipant::class, ['record' => $participant->getRouteKey()])
            ->fillForm([
                'first_name' => 'New',
                'last_name' => 'Person',
                'country_id' => $this->germany->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'first_name' => 'New',
            'last_name' => 'Person',
            'country_id' => $this->germany->id,
        ]);
    }

    public function test_participant_can_be_deleted(): void
    {
        $participant = Participant::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'country_id' => $this->estonia->id,
        ]);

        Livewire::test(EditParticipant::class, ['record' => $participant->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
    }

    public function test_country_select_only_offers_project_countries(): void
    {
        Livewire::test(CreateParticipant::class)
            ->assertFormFieldExists('country_id', function ($field): bool {
                $options = $field->getOptions();

                return isset($options[$this->estonia->id])
                    && isset($options[$this->germany->id])
                    && count($options) === 2;
            });
    }
}
