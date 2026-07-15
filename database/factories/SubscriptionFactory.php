<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->bothify('##########'),
            'stripe_status' => 'active',
            'stripe_price' => config('services.stripe.prices.pro'),
            'quantity' => 1,
        ];
    }
}
