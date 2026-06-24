<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCountry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Nnjeim\World\Models\Country;
use Tests\TestCase;

class ProjectCountryTest extends TestCase
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

    public function test_project_country_can_be_created(): void
    {
        $project = Project::factory()->create();
        $country = Country::query()->where('iso2', 'EE')->firstOrFail();

        $pc = ProjectCountry::create([
            'project_id' => $project->id,
            'country_id' => $country->id,
            'default_travel_expense_limit' => 750.50,
        ]);

        $this->assertSame('750.50', $pc->fresh()->default_travel_expense_limit);
    }

    public function test_project_country_belongs_to_project_and_country(): void
    {
        $project = Project::factory()->create();
        $country = Country::query()->where('iso2', 'DE')->firstOrFail();

        $pc = ProjectCountry::factory()->create([
            'project_id' => $project->id,
            'country_id' => $country->id,
        ]);

        $this->assertTrue($pc->project->is($project));
        $this->assertTrue($pc->country->is($country));
    }

    public function test_project_has_many_countries(): void
    {
        $project = Project::factory()->create();
        $ee = Country::query()->where('iso2', 'EE')->firstOrFail();
        $de = Country::query()->where('iso2', 'DE')->firstOrFail();

        ProjectCountry::factory()->create(['project_id' => $project->id, 'country_id' => $ee->id]);
        ProjectCountry::factory()->create(['project_id' => $project->id, 'country_id' => $de->id]);

        $this->assertCount(2, $project->countries);
    }

    public function test_country_cannot_be_added_twice_to_same_project(): void
    {
        $project = Project::factory()->create();
        $country = Country::query()->where('iso2', 'EE')->firstOrFail();

        ProjectCountry::create(['project_id' => $project->id, 'country_id' => $country->id]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        ProjectCountry::create(['project_id' => $project->id, 'country_id' => $country->id]);
    }

    public function test_default_travel_expense_limit_can_be_null(): void
    {
        $project = Project::factory()->create();
        $country = Country::query()->where('iso2', 'EE')->firstOrFail();

        $pc = ProjectCountry::create([
            'project_id' => $project->id,
            'country_id' => $country->id,
        ]);

        $this->assertNull($pc->fresh()->default_travel_expense_limit);
    }
}
