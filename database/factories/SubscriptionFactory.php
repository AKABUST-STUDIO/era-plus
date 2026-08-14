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

    public function configure(): static
    {
        return $this->afterCreating(function (Subscription $subscription): void {
            $freeProjectPriceId = config('services.stripe.prices.project_free');

            if (! is_string($freeProjectPriceId) || $freeProjectPriceId === '') {
                return;
            }

            if ($subscription->items()->where('stripe_price', $freeProjectPriceId)->exists()) {
                return;
            }

            $subscription->items()->create([
                'stripe_id' => 'si_'.fake()->unique()->bothify('##########'),
                'stripe_product' => 'prod_project_free',
                'stripe_price' => $freeProjectPriceId,
                'quantity' => 1,
            ]);
        });
    }

    public function withSlots(int $quantity): static
    {
        return $this->afterCreating(function (Subscription $subscription) use ($quantity): void {
            $slotPriceId = config('services.stripe.prices.project_slot');

            if (! is_string($slotPriceId) || $slotPriceId === '') {
                return;
            }

            $subscription->items()->updateOrCreate(
                ['stripe_price' => $slotPriceId],
                [
                    'stripe_id' => 'si_'.fake()->unique()->bothify('##########'),
                    'stripe_product' => 'prod_project_slot',
                    'quantity' => $quantity,
                ],
            );
        });
    }

    public function withoutFreeProjectItem(): static
    {
        return $this->afterCreating(function (Subscription $subscription): void {
            $freeProjectPriceId = config('services.stripe.prices.project_free');

            if (! is_string($freeProjectPriceId) || $freeProjectPriceId === '') {
                return;
            }

            $subscription->items()->where('stripe_price', $freeProjectPriceId)->delete();
        });
    }
}
