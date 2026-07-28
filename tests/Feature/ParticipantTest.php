<?php

namespace Tests\Feature;

use App\Enums\Project\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use App\Models\Project\ProjectParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParticipantTest extends TestCase
{
    use RefreshDatabase;

    private function countryId(): int
    {
        $existing = DB::table('countries')->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        DB::table('countries')->insert([
            'iso2' => 'ES',
            'name' => 'Spain',
            'status' => 1,
            'phone_code' => '0',
            'iso3' => 'ESP',
            'region' => 'Europe',
            'subregion' => 'Europe',
        ]);

        return (int) DB::table('countries')->where('iso2', 'ES')->value('id');
    }

    private function sendingOrganization(string $name = 'Universidad'): ParticipantOrganization
    {
        return ParticipantOrganization::create(['name' => $name]);
    }

    public function test_participants_table_has_no_project_organization_or_user_columns(): void
    {
        $this->assertFalse(Schema::hasColumn('participants', 'project_id'));
        $this->assertFalse(Schema::hasColumn('participants', 'organization_id'));
        $this->assertFalse(Schema::hasColumn('participants', 'user_id'));
    }

    public function test_participant_email_is_nullable(): void
    {
        $participant = Participant::factory()->create(['email' => null]);

        $this->assertNull($participant->email);
    }

    public function test_participant_participates_in_multiple_projects_through_pivot(): void
    {
        $organization = Organization::factory()->create();
        $projectA = Project::factory()->for($organization)->create();
        $projectB = Project::factory()->for($organization)->create();

        $participant = Participant::factory()->create();

        $projectA->addParticipant($participant, $this->countryId(), $this->sendingOrganization('Universidad A'));
        $projectB->addParticipant($participant, $this->countryId(), $this->sendingOrganization('Universidad B'));

        $this->assertCount(2, $participant->fresh()->projects);
        $this->assertTrue($projectA->fresh()->participants->contains($participant));
        $this->assertTrue($projectB->fresh()->participants->contains($participant));
    }

    public function test_pivot_is_unique_per_project_and_participable(): void
    {
        $project = Project::factory()->create();
        $participant = Participant::factory()->create();
        $sending = $this->sendingOrganization();

        $project->addParticipant($participant, $this->countryId(), $sending);
        $project->addParticipant($participant, $this->countryId(), $sending);

        $this->assertDatabaseCount('project_participant', 1);
    }

    public function test_pivot_stores_morph_type_and_id(): void
    {
        $project = Project::factory()->create();
        $participant = Participant::factory()->create();

        $project->addParticipant($participant, $this->countryId(), $this->sendingOrganization());

        $this->assertDatabaseHas('project_participant', [
            'project_id' => $project->id,
            'participable_type' => $participant->getMorphClass(),
            'participable_id' => $participant->id,
        ]);
    }

    public function test_user_can_be_a_participable_via_morph(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $sending = $this->sendingOrganization('Employer');

        ProjectParticipant::create([
            'project_id' => $project->id,
            'participable_type' => $user->getMorphClass(),
            'participable_id' => $user->id,
            'country_id' => $this->countryId(),
            'sending_organization_type' => $sending->getMorphClass(),
            'sending_organization_id' => $sending->getKey(),
        ]);

        $this->assertDatabaseHas('project_participant', [
            'project_id' => $project->id,
            'participable_type' => $user->getMorphClass(),
            'participable_id' => $user->id,
        ]);
    }

    public function test_project_participables_returns_all_pivot_rows(): void
    {
        $project = Project::factory()->create();
        $participant = Participant::factory()->create();
        $user = User::factory()->create();
        $sending = $this->sendingOrganization();

        $project->addParticipant($participant, $this->countryId(), $sending);
        ProjectParticipant::create([
            'project_id' => $project->id,
            'participable_type' => $user->getMorphClass(),
            'participable_id' => $user->id,
            'country_id' => $this->countryId(),
            'sending_organization_type' => $sending->getMorphClass(),
            'sending_organization_id' => $sending->getKey(),
        ]);

        $this->assertCount(2, $project->fresh()->participables);
    }

    public function test_pivot_is_verified_only_when_participable_is_a_user(): void
    {
        $project = Project::factory()->create();
        $participant = Participant::factory()->create();
        $user = User::factory()->create();
        $sending = $this->sendingOrganization();

        $project->addParticipant($participant, $this->countryId(), $sending);
        $verified = ProjectParticipant::create([
            'project_id' => $project->id,
            'participable_type' => $user->getMorphClass(),
            'participable_id' => $user->id,
            'country_id' => $this->countryId(),
            'sending_organization_type' => $sending->getMorphClass(),
            'sending_organization_id' => $sending->getKey(),
        ]);

        $unverified = ProjectParticipant::query()
            ->where('participable_type', $participant->getMorphClass())
            ->where('participable_id', $participant->id)
            ->firstOrFail();

        $this->assertTrue($verified->isVerified());
        $this->assertFalse($unverified->isVerified());
    }

    public function test_participants_by_project_read_through_pivot_not_roles(): void
    {
        $project = Project::factory()->create();
        $coordinator = User::factory()->create();
        $coordinator->joinProject($project, ProjectRole::Admin);

        $participant = Participant::factory()->create();
        $project->addParticipant($participant, $this->countryId(), $this->sendingOrganization());

        $this->assertCount(1, $project->fresh()->participants);
        $this->assertTrue($project->fresh()->participants->contains($participant));
    }
}
