<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Nnjeim\World\Models\Country;
use Tests\TestCase;

class ParticipantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('countries')->insert([
            ['iso2' => 'EE', 'iso3' => 'EST', 'name' => 'Estonia', 'status' => 1, 'phone_code' => '372', 'region' => 'Europe', 'subregion' => 'Northern Europe'],
            ['iso2' => 'DE', 'iso3' => 'DEU', 'name' => 'Germany', 'status' => 1, 'phone_code' => '49', 'region' => 'Europe', 'subregion' => 'Western Europe'],
        ]);
    }

    public function test_participant_belongs_to_project_organization_country(): void
    {
        $project = Project::factory()->create();
        $country = Country::query()->where('iso2', 'EE')->firstOrFail();

        $participant = Participant::create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'country_id' => $country->id,
            'first_name' => 'Maria',
            'last_name' => 'Tamm',
            'email' => 'maria@example.com',
        ]);

        $this->assertTrue($participant->project->is($project));
        $this->assertSame($project->organization_id, $participant->organization_id);
        $this->assertTrue($participant->country->is($country));
    }

    public function test_participant_can_be_linked_to_user(): void
    {
        $project = Project::factory()->create();
        $country = Country::query()->where('iso2', 'EE')->firstOrFail();
        $user = User::factory()->create();

        $participant = Participant::create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'country_id' => $country->id,
            'user_id' => $user->id,
            'first_name' => 'A',
            'last_name' => 'B',
        ]);

        $this->assertTrue($participant->user->is($user));
    }

    public function test_participant_user_is_optional(): void
    {
        $participant = Participant::factory()->create(['user_id' => null]);

        $this->assertNull($participant->user);
    }

    public function test_project_has_many_participants(): void
    {
        $project = Project::factory()->create();
        Participant::factory()->count(3)->create([
            'project_id' => $project->id,
            'organization_id' => $project->organization_id,
        ]);

        $this->assertCount(3, $project->fresh()->participants);
    }

    public function test_full_name_accessor(): void
    {
        $participant = Participant::factory()->make([
            'first_name' => 'Anne',
            'last_name' => 'Mets',
        ]);

        $this->assertSame('Anne Mets', $participant->full_name);
    }

    public function test_participants_isolated_by_project(): void
    {
        $org = Organization::factory()->create();
        $a = Project::factory()->for($org)->create();
        $b = Project::factory()->for($org)->create();

        Participant::factory()->count(2)->create(['project_id' => $a->id, 'organization_id' => $org->id]);
        Participant::factory()->count(3)->create(['project_id' => $b->id, 'organization_id' => $org->id]);

        $this->assertCount(2, $a->fresh()->participants);
        $this->assertCount(3, $b->fresh()->participants);
    }
}
