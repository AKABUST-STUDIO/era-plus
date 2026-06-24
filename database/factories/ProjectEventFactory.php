<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectEvent>
 */
class ProjectEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory()->create();
        $start = fake()->dateTimeBetween('-1 month', '+3 months');

        return [
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'title' => fake()->catchPhrase(),
            'description' => fake()->optional()->paragraph(),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+'.fake()->numberBetween(1, 8).' hours'),
            'all_day' => false,
            'location' => fake()->optional()->city(),
        ];
    }
}
