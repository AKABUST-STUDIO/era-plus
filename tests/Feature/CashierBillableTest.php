<?php

namespace Tests\Feature;

use App\Enums\SubscriptionTier;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Cashier;
use Tests\TestCase;

class CashierBillableTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_uses_billable_trait(): void
    {
        $this->assertContains(Billable::class, class_uses_recursive(Organization::class));
    }

    public function test_cashier_customer_model_is_organization(): void
    {
        $this->assertSame(Organization::class, Cashier::$customerModel);
    }

    public function test_subscription_tier_defaults_to_free(): void
    {
        $organization = Organization::factory()->create();

        $this->assertSame(SubscriptionTier::Free, $organization->fresh()->subscription_tier);
    }

    public function test_free_tier_allows_one_project(): void
    {
        $organization = Organization::factory()->create();

        $this->assertTrue($organization->canCreateProject());

        Project::factory()->for($organization)->create();

        $this->assertFalse($organization->fresh()->canCreateProject());
    }

    public function test_extra_seats_raise_free_tier_limit(): void
    {
        $organization = Organization::factory()->create(['extra_project_seats' => 2]);

        Project::factory()->for($organization)->count(2)->create();

        $this->assertTrue($organization->fresh()->canCreateProject());

        Project::factory()->for($organization)->create();

        $this->assertFalse($organization->fresh()->canCreateProject());
    }

    public function test_pro_tier_has_unlimited_projects(): void
    {
        $organization = Organization::factory()->create([
            'subscription_tier' => SubscriptionTier::Pro,
        ]);

        Project::factory()->for($organization)->count(10)->create();

        $this->assertTrue($organization->fresh()->canCreateProject());
        $this->assertNull($organization->fresh()->projectLimit());
    }

    public function test_premium_tier_has_unlimited_projects(): void
    {
        $organization = Organization::factory()->create([
            'subscription_tier' => SubscriptionTier::Premium,
        ]);

        Project::factory()->for($organization)->count(10)->create();

        $this->assertTrue($organization->fresh()->canCreateProject());
    }

    public function test_organization_can_have_stripe_id(): void
    {
        $organization = Organization::factory()->create([
            'stripe_id' => 'cus_test_123',
        ]);

        $this->assertTrue($organization->hasStripeId());
    }
}
