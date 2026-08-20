<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

function roleIdFor(User $user, Organization $organization): ?int
{
    return $user->organizations()
        ->whereKey($organization->getKey())
        ->first()
        ?->member
        ->role_id;
}

test('fresh join sets the requested role', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->joinOrganization($organization, OrganizationRole::Admin);

    $this->assertSame(
        $organization->roleFor(OrganizationRole::Admin)->id,
        roleIdFor($user, $organization),
    );
    $this->assertTrue($user->fresh()->isOrgAdmin($organization));
});

test('fresh join defaults to member role', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->joinOrganization($organization);

    $this->assertSame(
        $organization->roleFor(OrganizationRole::Member)->id,
        roleIdFor($user, $organization),
    );
});

test('rejoining upgrades an existing members role', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->joinOrganization($organization, OrganizationRole::Member);
    $user->joinOrganization($organization, OrganizationRole::Admin);

    $this->assertSame(
        $organization->roleFor(OrganizationRole::Admin)->id,
        roleIdFor($user, $organization),
    );
    $this->assertTrue($user->fresh()->isOrgAdmin($organization));
});

test('rejoining does not duplicate the pivot row', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->joinOrganization($organization, OrganizationRole::Member);
    $user->joinOrganization($organization, OrganizationRole::Admin);

    $this->assertSame(1, $user->organizations()->whereKey($organization->getKey())->count());
});
