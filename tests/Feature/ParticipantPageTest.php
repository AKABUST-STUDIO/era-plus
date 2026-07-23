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
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ParticipantPageTest extends TestCase
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
        $this->user->joinProject($this->project, ProjectRole::Admin);

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

    public function test_list_page_shows_participants(): void
    {
        Participant::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'first_name' => 'Anne',
            'last_name' => 'Tamm',
        ]);

        Livewire::test(ListParticipants::class)
            ->assertSee('Anne')
            ->assertSee('Tamm');
    }

    public function test_participant_can_be_created(): void
    {
        Livewire::test(CreateParticipant::class)
            ->fillForm([
                'first_name' => 'Hans',
                'last_name' => 'Meier',
                'email' => 'hans@example.com',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('participants', [
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'first_name' => 'Hans',
            'last_name' => 'Meier',
            'email' => 'hans@example.com',
        ]);
    }

    public function test_create_requires_first_last_name(): void
    {
        Livewire::test(CreateParticipant::class)
            ->fillForm([
                'first_name' => null,
                'last_name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['first_name', 'last_name']);
    }

    public function test_participant_can_be_edited(): void
    {
        $participant = Participant::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        Livewire::test(EditParticipant::class, ['record' => $participant->getRouteKey()])
            ->fillForm([
                'first_name' => 'New',
                'last_name' => 'Person',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'first_name' => 'New',
            'last_name' => 'Person',
        ]);
    }

    public function test_participant_can_be_deleted(): void
    {
        $participant = Participant::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
        ]);

        Livewire::test(EditParticipant::class, ['record' => $participant->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
    }
}
