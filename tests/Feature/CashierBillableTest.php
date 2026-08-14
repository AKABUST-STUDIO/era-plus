<?php

namespace Tests\Feature;

use App\Enums\Subscription\SubscriptionTier;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Billable;
use Tests\TestCase;

class CashierBillableTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_uses_billable_trait(): void
    {
        $this->assertContains(Billable::class, class_uses_recursive(User::class));
    }

    public function test_subscription_tier_defaults_to_basic_without_a_subscription(): void
    {
        $organization = Organization::factory()->create();

        $this->assertSame(SubscriptionTier::Basic, $organization->fresh()->subscription_tier);
    }

    public function test_tier_is_derived_from_the_linked_subscription(): void
    {
        $organization = Organization::factory()->subscribed(SubscriptionTier::Pro)->create();

        $this->assertSame(SubscriptionTier::Pro, $organization->fresh()->subscription_tier);
    }

    public function test_org_without_paid_slots_allows_only_the_free_project(): void
    {
        $organization = Organization::factory()->create();

        $this->assertTrue($organization->canCreateProject());

        Project::factory()->for($organization)->create();

        $this->assertFalse($organization->fresh()->canCreateProject());
    }
}
