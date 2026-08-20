<?php

use App\Models\Organization;
use App\Models\User;

test('organization can be created', function (): void {
    $organization = Organization::factory()->create([
        'name' => 'Acme Corporation',
    ]);

    $this->assertDatabaseHas('organizations', [
        'name' => 'Acme Corporation',
    ]);
    $this->assertNotNull($organization->slug);
});

test('organization slug is generated from name', function (): void {
    $organization = Organization::factory()->create([
        'name' => 'Test Organization Name',
    ]);

    $this->assertEquals('test-organization-name', $organization->slug);
});

test('organization can have users', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $user->joinOrganization($organization);

    $this->assertTrue($organization->users->contains($user));
    $this->assertTrue($user->organizations->contains($organization));
});

test('user can belong to multiple organizations', function (): void {
    $user = User::factory()->create();
    $org1 = Organization::factory()->create(['name' => 'Org One']);
    $org2 = Organization::factory()->create(['name' => 'Org Two']);

    $user->joinOrganization($org1);
    $user->joinOrganization($org2);

    $this->assertCount(2, $user->fresh()->organizations);
});

test('user can access tenant they belong to', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->joinOrganization($organization);

    $this->assertTrue($user->canAccessTenant($organization));
});

test('user cannot access tenant they do not belong to', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $this->assertFalse($user->canAccessTenant($organization));
});

test('organization uses slug as route key', function (): void {
    $organization = Organization::factory()->create([
        'name' => 'My Organization',
    ]);

    $this->assertEquals('slug', $organization->getRouteKeyName());
});
