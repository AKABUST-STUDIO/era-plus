<?php

namespace Tests\Feature;

use App\Enums\Subscription\SubscriptionTier;
use Tests\TestCase;

class SubscriptionTierTest extends TestCase
{
    public function test_only_basic_pro_and_trial_exist(): void
    {
        $values = array_map(fn (SubscriptionTier $tier): string => $tier->value, SubscriptionTier::cases());

        $this->assertSame(['basic', 'pro', 'trial'], $values);
        $this->assertNull(SubscriptionTier::tryFrom('premium'));
    }

    public function test_labels(): void
    {
        $this->assertSame('Basic', SubscriptionTier::Basic->getLabel());
        $this->assertSame('Pro', SubscriptionTier::Pro->getLabel());
        $this->assertSame('Trial', SubscriptionTier::Trial->getLabel());
    }

    public function test_basic_is_limited_to_one_project_pro_and_trial_are_unlimited(): void
    {
        $this->assertSame(1, SubscriptionTier::Basic->baseProjectLimit());
        $this->assertNull(SubscriptionTier::Pro->baseProjectLimit());
        $this->assertNull(SubscriptionTier::Trial->baseProjectLimit());
    }

    public function test_stripe_price_id_reads_the_matching_config_key(): void
    {
        config()->set('services.stripe.prices.trial', 'price_trial_123');

        $this->assertSame('price_trial_123', SubscriptionTier::Trial->stripePriceId());
    }

    public function test_from_stripe_price_id_round_trips(): void
    {
        config()->set('services.stripe.prices.pro', 'price_pro_123');

        $this->assertSame(SubscriptionTier::Pro, SubscriptionTier::fromStripePriceId('price_pro_123'));
    }

    public function test_from_stripe_price_id_returns_null_for_null_or_unknown(): void
    {
        $this->assertNull(SubscriptionTier::fromStripePriceId(null));
        $this->assertNull(SubscriptionTier::fromStripePriceId('price_does_not_exist'));
    }
}
