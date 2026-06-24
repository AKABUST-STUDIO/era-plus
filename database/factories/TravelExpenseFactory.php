<?php

namespace Database\Factories;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelExpense>
 */
class TravelExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $participant = Participant::factory()->create();

        return [
            'organization_id' => $participant->organization_id,
            'project_id' => $participant->project_id,
            'participant_id' => $participant->id,
            'amount' => fake()->randomFloat(2, 50, 1200),
            'occurred_at' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
            'description' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function forParticipant(Participant $participant): self
    {
        return $this->state([
            'organization_id' => $participant->organization_id,
            'project_id' => $participant->project_id,
            'participant_id' => $participant->id,
        ]);
    }
}
