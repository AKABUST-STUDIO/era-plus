<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JoinOrganizationTest extends TestCase
{
    use RefreshDatabase;

    private function roleIdFor(User $user, Organization $organization): ?int
    {
        return $user->organizations()
            ->whereKey($organization->getKey())
            ->first()
            ?->member
            ->role_id;
    }

    public function test_fresh_join_sets_the_requested_role(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $user->joinOrganization($organization, OrganizationRole::Admin);

        $this->assertSame(
            $organization->roleFor(OrganizationRole::Admin)->id,
            $this->roleIdFor($user, $organization),
        );
        $this->assertTrue($user->fresh()->isOrgAdmin($organization));
    }

    public function test_fresh_join_defaults_to_member_role(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $user->joinOrganization($organization);

        $this->assertSame(
            $organization->roleFor(OrganizationRole::Member)->id,
            $this->roleIdFor($user, $organization),
        );
    }

    public function test_rejoining_upgrades_an_existing_members_role(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $user->joinOrganization($organization, OrganizationRole::Member);
        $user->joinOrganization($organization, OrganizationRole::Admin);

        $this->assertSame(
            $organization->roleFor(OrganizationRole::Admin)->id,
            $this->roleIdFor($user, $organization),
        );
        $this->assertTrue($user->fresh()->isOrgAdmin($organization));
    }

    public function test_rejoining_does_not_duplicate_the_pivot_row(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $user->joinOrganization($organization, OrganizationRole::Member);
        $user->joinOrganization($organization, OrganizationRole::Admin);

        $this->assertSame(1, $user->organizations()->whereKey($organization->getKey())->count());
    }
}
