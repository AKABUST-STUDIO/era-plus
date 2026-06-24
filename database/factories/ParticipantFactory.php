<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory()->create();

        return [
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'user_id' => null,
            'country_id' => $this->ensureCountryExists(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->optional()->safeEmail(),
        ];
    }

    private function ensureCountryExists(): int
    {
        $country = DB::table('countries')->inRandomOrder()->first();

        if ($country !== null) {
            return (int) $country->id;
        }

        return (int) DB::table('countries')->insertGetId([
            'iso2' => 'EE',
            'iso3' => 'EST',
            'name' => 'Estonia',
            'status' => 1,
            'phone_code' => '372',
            'region' => 'Europe',
            'subregion' => 'Northern Europe',
        ]);
    }
}
