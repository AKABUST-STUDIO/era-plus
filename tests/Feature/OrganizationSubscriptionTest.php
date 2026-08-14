<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Enums\Subscription\SubscriptionTier;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_defaults_to_basic_without_a_subscription(): void
    {
        $organization = Organization::factory()->create();

        $this->assertSame(SubscriptionTier::Basic, $organization->subscription_tier);
    }

    public function test_tier_is_derived_from_the_linked_subscription(): void
    {
        $organization = Organization::factory()->subscribed(SubscriptionTier::Pro)->create();

        $this->assertSame(SubscriptionTier::Pro, $organization->fresh()->subscription_tier);
    }

    public function test_owner_is_the_subscription_user(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();

        $this->assertTrue($organization->fresh()->owner()->is($owner));
    }

    public function test_every_organization_has_an_owner(): void
    {
        $organization = Organization::factory()->create();

        $this->assertNotNull($organization->subscription_id);
        $this->assertTrue($organization->owner()->is($organization->subscription->user));
    }

    public function test_owner_does_not_administer_the_organization_without_a_membership_role(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create()->fresh();

        $this->assertFalse($organization->users()->whereKey($owner->id)->exists());
        $this->assertFalse(app(ProjectAccess::class)->administersOrganization($owner, $organization));
        $this->assertFalse($owner->isOrgAdmin($organization));
    }

    public function test_owner_administers_the_organization_once_given_the_admin_role(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create()->fresh();
        $owner->joinOrganization($organization, OrganizationRole::Admin);

        $this->assertTrue(app(ProjectAccess::class)->administersOrganization($owner, $organization));
        $this->assertTrue($owner->isOrgAdmin($organization));
    }

    public function test_observer_provisions_organization_roles_on_create(): void
    {
        $organization = Organization::factory()->create();

        $this->assertNotNull($organization->roleFor(OrganizationRole::Admin));
        $this->assertNotNull($organization->roleFor(OrganizationRole::Member));
    }

    public function test_observer_provisions_project_roles_on_create(): void
    {
        $project = Project::factory()->create();

        $this->assertNotNull($project->roleFor(ProjectRole::Admin));
        $this->assertNotNull($project->roleFor(ProjectRole::Participant));
    }
}
