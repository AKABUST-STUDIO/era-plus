<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\ProjectParticipants\ProjectParticipantResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use App\Models\Project\ProjectParticipant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

function participantCountryId(): int
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

function participantSendingOrganization(string $name = 'Universidad'): ParticipantOrganization
{
    return ParticipantOrganization::create(['name' => $name]);
}

test('participants table has no project organization or user columns', function (): void {
    $this->assertFalse(Schema::hasColumn('participants', 'project_id'));
    $this->assertFalse(Schema::hasColumn('participants', 'organization_id'));
    $this->assertFalse(Schema::hasColumn('participants', 'user_id'));
});

test('participant email is nullable', function (): void {
    $participant = Participant::factory()->create(['email' => null]);

    $this->assertNull($participant->email);
});

test('participant participates in multiple projects through pivot', function (): void {
    $organization = Organization::factory()->create();
    $projectA = Project::factory()->for($organization)->create();
    $projectB = Project::factory()->for($organization)->create();

    $participant = Participant::factory()->create();

    $projectA->addParticipant($participant, participantCountryId(), participantSendingOrganization('Universidad A'));
    $projectB->addParticipant($participant, participantCountryId(), participantSendingOrganization('Universidad B'));

    $this->assertCount(2, $participant->fresh()->projects);
    $this->assertTrue($projectA->fresh()->participants->contains($participant));
    $this->assertTrue($projectB->fresh()->participants->contains($participant));
});

test('pivot is unique per project and participable', function (): void {
    $project = Project::factory()->create();
    $participant = Participant::factory()->create();
    $sending = participantSendingOrganization();

    $project->addParticipant($participant, participantCountryId(), $sending);
    $project->addParticipant($participant, participantCountryId(), $sending);

    $this->assertDatabaseCount('project_participant', 1);
});

test('pivot stores morph type and id', function (): void {
    $project = Project::factory()->create();
    $participant = Participant::factory()->create();

    $project->addParticipant($participant, participantCountryId(), participantSendingOrganization());

    $this->assertDatabaseHas('project_participant', [
        'project_id' => $project->id,
        'participable_type' => $participant->getMorphClass(),
        'participable_id' => $participant->id,
    ]);
});

test('user can be a participable via morph', function (): void {
    $project = Project::factory()->create();
    $user = User::factory()->create();
    $sending = participantSendingOrganization('Employer');

    ProjectParticipant::create([
        'project_id' => $project->id,
        'participable_type' => $user->getMorphClass(),
        'participable_id' => $user->id,
        'country_id' => participantCountryId(),
        'sending_organization_type' => $sending->getMorphClass(),
        'sending_organization_id' => $sending->getKey(),
    ]);

    $this->assertDatabaseHas('project_participant', [
        'project_id' => $project->id,
        'participable_type' => $user->getMorphClass(),
        'participable_id' => $user->id,
    ]);
});

test('project participables returns all pivot rows', function (): void {
    $project = Project::factory()->create();
    $participant = Participant::factory()->create();
    $user = User::factory()->create();
    $sending = participantSendingOrganization();

    $project->addParticipant($participant, participantCountryId(), $sending);
    ProjectParticipant::create([
        'project_id' => $project->id,
        'participable_type' => $user->getMorphClass(),
        'participable_id' => $user->id,
        'country_id' => participantCountryId(),
        'sending_organization_type' => $sending->getMorphClass(),
        'sending_organization_id' => $sending->getKey(),
    ]);

    $this->assertCount(2, $project->fresh()->participables);
});

test('pivot is verified only when participable is a user', function (): void {
    $project = Project::factory()->create();
    $participant = Participant::factory()->create();
    $user = User::factory()->create();
    $sending = participantSendingOrganization();

    $project->addParticipant($participant, participantCountryId(), $sending);
    $verified = ProjectParticipant::create([
        'project_id' => $project->id,
        'participable_type' => $user->getMorphClass(),
        'participable_id' => $user->id,
        'country_id' => participantCountryId(),
        'sending_organization_type' => $sending->getMorphClass(),
        'sending_organization_id' => $sending->getKey(),
    ]);

    $unverified = ProjectParticipant::query()
        ->where('participable_type', $participant->getMorphClass())
        ->where('participable_id', $participant->id)
        ->firstOrFail();

    $this->assertTrue($verified->isVerified());
    $this->assertFalse($unverified->isVerified());
});

test('participants by project read through pivot not roles', function (): void {
    $project = Project::factory()->create();
    $coordinator = User::factory()->create();
    $coordinator->joinProject($project, ProjectRole::Admin);

    $participant = Participant::factory()->create();
    $project->addParticipant($participant, participantCountryId(), participantSendingOrganization());

    $this->assertCount(1, $project->fresh()->participants);
    $this->assertTrue($project->fresh()->participants->contains($participant));
});

test('participants list page is served from the participants url', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization, OrganizationRole::Admin);
    $project = Project::factory()->for($organization)->create();
    $user->joinProject($project, ProjectRole::Admin);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($project);
    URL::defaults(['organization' => $organization->slug]);

    $url = ProjectParticipantResource::getUrl(tenant: $project);

    $this->assertStringEndsWith('/'.$project->slug.'/participants', $url);

    $this->get($url)->assertSuccessful();
});
