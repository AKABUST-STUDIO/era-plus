<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_can_be_created(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Acme Corporation',
        ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Acme Corporation',
        ]);
        $this->assertNotNull($organization->slug);
    }

    public function test_organization_slug_is_generated_from_name(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization Name',
        ]);

        $this->assertEquals('test-organization-name', $organization->slug);
    }

    public function test_organization_can_have_users(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $user->joinOrganization($organization);

        $this->assertTrue($organization->users->contains($user));
        $this->assertTrue($user->organizations->contains($organization));
    }

    public function test_user_can_belong_to_multiple_organizations(): void
    {
        $user = User::factory()->create();
        $org1 = Organization::factory()->create(['name' => 'Org One']);
        $org2 = Organization::factory()->create(['name' => 'Org Two']);

        $user->joinOrganization($org1);
        $user->joinOrganization($org2);

        $this->assertCount(2, $user->fresh()->organizations);
    }

    public function test_user_can_access_tenant_they_belong_to(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $user->joinOrganization($organization);

        $this->assertTrue($user->canAccessTenant($organization));
    }

    public function test_user_cannot_access_tenant_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->assertFalse($user->canAccessTenant($organization));
    }

    public function test_organization_uses_slug_as_route_key(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'My Organization',
        ]);

        $this->assertEquals('slug', $organization->getRouteKeyName());
    }
}
