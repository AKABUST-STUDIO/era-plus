<?php

namespace Database\Factories;

use App\Enums\SupportRequest\SupportRequestStatus;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportRequest>
 */
class SupportRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'status' => SupportRequestStatus::Open->value,
            'priority' => 'normal',
        ];
    }
}
