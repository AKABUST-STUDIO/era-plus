<?php

namespace Tests\Feature;

use App\Enums\SubscriptionTier;
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

    public function test_basic_tier_allows_one_project(): void
    {
        $organization = Organization::factory()->create();

        $this->assertTrue($organization->canCreateProject());

        Project::factory()->for($organization)->create();

        $this->assertFalse($organization->fresh()->canCreateProject());
    }

    public function test_pro_tier_has_unlimited_projects(): void
    {
        $organization = Organization::factory()->subscribed(SubscriptionTier::Pro)->create();

        Project::factory()->for($organization)->count(10)->create();

        $this->assertTrue($organization->fresh()->canCreateProject());
        $this->assertNull($organization->fresh()->projectLimit());
    }

    public function test_trial_tier_has_unlimited_projects(): void
    {
        $organization = Organization::factory()->subscribed(SubscriptionTier::Trial)->create();

        Project::factory()->for($organization)->count(10)->create();

        $this->assertNull($organization->fresh()->projectLimit());
    }
}
