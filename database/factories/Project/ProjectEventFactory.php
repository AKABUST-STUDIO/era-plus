<?php

namespace Database\Factories\Project;

use App\Models\Project;
use App\Models\Project\ProjectEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectEvent>
 */
class ProjectEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+30 days');
        $end = (clone $start)->modify('+2 hours');

        return [
            'project_id' => Project::factory(),
            'google_event_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'location' => fake()->optional()->city(),
            'starts_at' => $start,
            'ends_at' => $end,
            'created_by' => null,
            'google_updated_at' => null,
        ];
    }
}
