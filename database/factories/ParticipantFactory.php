<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->optional()->safeEmail(),
        ];
    }
}
