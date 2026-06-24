<?php

namespace Database\Factories;

use App\Enums\FinanceOperation;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinanceEntry>
 */
class FinanceEntryFactory extends Factory
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
            'operation' => fake()->randomElement(FinanceOperation::cases()),
            'amount' => fake()->randomFloat(2, 1, 9999),
            'description' => fake()->optional()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'created_by' => null,
        ];
    }

    public function add(): self
    {
        return $this->state(['operation' => FinanceOperation::Add]);
    }

    public function subtract(): self
    {
        return $this->state(['operation' => FinanceOperation::Subtract]);
    }

    public function forProject(Project $project): self
    {
        return $this->state([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
        ]);
    }
}
