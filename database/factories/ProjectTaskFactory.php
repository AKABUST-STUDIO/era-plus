<?php

namespace Database\Factories;

use App\Enums\ProjectTask\ProjectTaskStatus;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTask>
 */
class ProjectTaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        return [
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'assigned_by' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => ProjectTaskStatus::Open,
            'due_date' => fake()->optional()->dateTimeBetween('+1 day', '+3 months')?->format('Y-m-d'),
            'completed_at' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state([
            'status' => ProjectTaskStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function overdue(): self
    {
        return $this->state([
            'status' => ProjectTaskStatus::Open,
            'due_date' => now()->subWeek()->format('Y-m-d'),
        ]);
    }
}
