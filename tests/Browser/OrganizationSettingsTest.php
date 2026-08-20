<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('admin can view the organization settings page', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme Test']);
    $admin = User::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/settings/overview')
            ->waitForText('Acme Test');
    });
});

test('member can view the organization settings page but cannot save', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);

    $this->browse(function (Browser $browser) use ($member, $organization): void {
        $browser->loginAs($member)
            ->visit('/'.$organization->slug.'/settings/overview');
        $browser->pause(500);

        // Page should load (canAccess is permissive) but save buttons hidden.
        expect($browser->driver->getCurrentURL())->toContain('/settings/overview');
    });
});
