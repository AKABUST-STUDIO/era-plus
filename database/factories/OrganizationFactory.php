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
        ];
    }

    public function subscribed(SubscriptionTier $tier = SubscriptionTier::Pro, ?User $owner = null): static
    {
        return $this->afterCreating(function (Organization $organization) use ($tier, $owner): void {
            $subscription = Subscription::factory()
                ->for($owner ?? User::factory()->create(), 'user')
                ->create(['stripe_price' => $tier->stripePriceId()]);

            $organization->update(['subscription_id' => $subscription->id]);
        });
    }

    public function ownedBy(User $owner, SubscriptionTier $tier = SubscriptionTier::Basic): static
    {
        return $this->subscribed($tier, $owner);
    }
}
