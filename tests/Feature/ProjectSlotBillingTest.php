<?php

namespace Tests\Feature;

use App\Enums\Subscription\SubscriptionTier;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSlotBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_org_has_one_free_project_slot_and_no_paid_slots(): void
    {
        $organization = Organization::factory()->create();

        $this->assertSame(1, $organization->freeProjectAllowance());
        $this->assertSame(0, $organization->purchasedProjectSlots());
        $this->assertSame(1, $organization->projectLimit());
    }

    public function test_first_project_uses_the_free_slot(): void
    {
        $organization = Organization::factory()->create();

        Project::factory()->for($organization)->create();

        $this->assertSame(1, $organization->fresh()->activeProjectCount());
        $this->assertFalse($organization->fresh()->canCreateProject());
    }

    public function test_second_project_is_blocked_without_paid_slots(): void
    {
        $organization = Organization::factory()->create();
        Project::factory()->for($organization)->create();

        $this->assertFalse($organization->fresh()->canCreateProject());
    }

    public function test_second_project_is_allowed_with_one_paid_slot(): void
    {
        $organization = Organization::factory()->withProjectSlots(1)->create();

        Project::factory()->for($organization)->create();

        $this->assertSame(2, $organization->fresh()->projectLimit());
        $this->assertTrue($organization->fresh()->canCreateProject());

        Project::factory()->for($organization)->create();

        $this->assertFalse($organization->fresh()->canCreateProject());
    }

    public function test_can_reduce_slots_when_new_quantity_still_covers_active_projects(): void
    {
        $organization = Organization::factory()->withProjectSlots(3)->create();
        Project::factory()->for($organization)->count(3)->create();

        $organization = $organization->fresh();

        $this->assertTrue($organization->canReduceProjectSlotsTo(2));
        $this->assertTrue($organization->canReduceProjectSlotsTo(3));
    }

    public function test_cannot_reduce_slots_below_active_project_count_minus_free_allowance(): void
    {
        $organization = Organization::factory()->withProjectSlots(3)->create();
        Project::factory()->for($organization)->count(4)->create();

        $organization = $organization->fresh();

        $this->assertFalse($organization->canReduceProjectSlotsTo(2));
        $this->assertTrue($organization->canReduceProjectSlotsTo(3));
    }

    public function test_subscription_tier_reads_from_subscription_stripe_price(): void
    {
        $organization = Organization::factory()->withProjectSlots(2, SubscriptionTier::Pro)->create();

        $organization = $organization->fresh();

        $this->assertCount(2, $organization->subscription->items);
        $this->assertSame(SubscriptionTier::Pro, $organization->subscription->tier());
        $this->assertSame(SubscriptionTier::Pro, $organization->subscription_tier);
    }

    public function test_subscription_tier_falls_back_to_basic_when_stripe_price_does_not_match(): void
    {
        $organization = Organization::factory()->create();

        Subscription::query()->whereKey($organization->subscription_id)->update(['stripe_price' => null]);

        $organization = $organization->fresh();

        $this->assertSame(SubscriptionTier::Basic, $organization->subscription_tier);
    }
}
