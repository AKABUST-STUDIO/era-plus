<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Enums\Subscription\SubscriptionTier;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;

test('tier is derived from the linked subscription', function (): void {
    $organization = Organization::factory()->create();

    $this->assertSame(SubscriptionTier::Pro, $organization->fresh()->subscription_tier);
});

test('owner is the subscription user', function (): void {
    $owner = User::factory()->create();
    $organization = Organization::factory()->ownedBy($owner)->create();

    $this->assertTrue($organization->fresh()->owner()->is($owner));
});

test('every organization has an owner', function (): void {
    $organization = Organization::factory()->create();

    $this->assertNotNull($organization->subscription_id);
    $this->assertTrue($organization->owner()->is($organization->subscription->user));
});

test('owner does not administer the organization without a membership role', function (): void {
    $owner = User::factory()->create();
    $organization = Organization::factory()->ownedBy($owner)->create()->fresh();

    $this->assertFalse($organization->users()->whereKey($owner->id)->exists());
    $this->assertFalse(app(ProjectAccess::class)->administersOrganization($owner, $organization));
    $this->assertFalse($owner->isOrgAdmin($organization));
});

test('owner administers the organization once given the admin role', function (): void {
    $owner = User::factory()->create();
    $organization = Organization::factory()->ownedBy($owner)->create()->fresh();
    $owner->joinOrganization($organization, OrganizationRole::Admin);

    $this->assertTrue(app(ProjectAccess::class)->administersOrganization($owner, $organization));
    $this->assertTrue($owner->isOrgAdmin($organization));
});

test('observer provisions organization roles on create', function (): void {
    $organization = Organization::factory()->create();

    $this->assertNotNull($organization->roleFor(OrganizationRole::Admin));
    $this->assertNotNull($organization->roleFor(OrganizationRole::Member));
});

test('observer provisions project roles on create', function (): void {
    $project = Project::factory()->create();

    $this->assertNotNull($project->roleFor(ProjectRole::Admin));
    $this->assertNotNull($project->roleFor(ProjectRole::Participant));
});
