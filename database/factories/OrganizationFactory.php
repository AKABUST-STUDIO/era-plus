<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'subscription_id' => Subscription::factory(),
        ];
    }

    public function ownedBy(User $owner): static
    {
        return $this->state(fn (): array => [
            'subscription_id' => Subscription::factory()->for($owner, 'user'),
        ]);
    }
}
