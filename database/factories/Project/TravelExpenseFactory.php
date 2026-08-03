<?php

namespace Database\Factories\Project;

use App\Enums\Project\TransportationType;
use App\Enums\Project\TravelType;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelExpense>
 */
class TravelExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 20, 600);

        return [
            'travel_type' => fake()->randomElement(TravelType::cases()),
            'transportation_type' => fake()->randomElement(TransportationType::cases()),
            'from' => fake()->city(),
            'to' => fake()->city(),
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'cost' => $cost,
            'currency' => 'EUR',
            'cost_eur' => $cost,
        ];
    }

    public function forParticipant(ProjectParticipant $projectParticipant): self
    {
        return $this->state(fn (): array => [
            'project_participant_id' => $projectParticipant->id,
        ]);
    }

    public function costing(float $costEur): self
    {
        return $this->state(fn (): array => [
            'cost' => $costEur,
            'currency' => 'EUR',
            'cost_eur' => $costEur,
        ]);
    }
}
