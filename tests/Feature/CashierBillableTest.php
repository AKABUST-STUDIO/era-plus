<?php

use App\Enums\Subscription\SubscriptionTier;
use App\Models\Organization;
use App\Models\User;
use Laravel\Cashier\Billable;

test('user uses billable trait', function (): void {
    $this->assertContains(Billable::class, class_uses_recursive(User::class));
});

test('tier is derived from the linked subscription', function (): void {
    $organization = Organization::factory()->create();

    $this->assertSame(SubscriptionTier::Pro, $organization->fresh()->subscription_tier);
});
