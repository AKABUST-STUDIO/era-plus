<?php

namespace Database\Factories;

use App\Enums\Subscription\SubscriptionTier;
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
            'subscription_id' => Subscription::factory()->state([
                'stripe_price' => SubscriptionTier::Basic->stripePriceId(),
            ]),
        ];
    }

    public function subscribed(SubscriptionTier $tier = SubscriptionTier::Pro, ?User $owner = null): static
    {
        return $this->state(fn (): array => [
            'subscription_id' => Subscription::factory()
                ->for($owner ?? User::factory(), 'user')
                ->create(['stripe_price' => $tier->stripePriceId()])
                ->id,
        ]);
    }

    public function ownedBy(User $owner, SubscriptionTier $tier = SubscriptionTier::Basic): static
    {
        return $this->subscribed($tier, $owner);
    }

    public function withProjectSlots(int $quantity, SubscriptionTier $tier = SubscriptionTier::Basic, ?User $owner = null): static
    {
        return $this->state(fn (): array => [
            'subscription_id' => Subscription::factory()
                ->for($owner ?? User::factory(), 'user')
                ->withSlots($quantity)
                ->create(['stripe_price' => $tier->stripePriceId()])
                ->id,
        ]);
    }
}
