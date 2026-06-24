<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectCountry>
 */
class ProjectCountryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'country_id' => $this->ensureCountryExists(),
            'default_travel_expense_limit' => fake()->randomFloat(2, 100, 1500),
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
