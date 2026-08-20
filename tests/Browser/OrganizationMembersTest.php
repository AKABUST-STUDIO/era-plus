<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('admin can view the users page and see themselves', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['name' => 'Alice Admin']);
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/users')
            ->waitForText('Alice Admin')
            ->assertSee('Alice Admin')
            ->assertSee('Admin');
    });
});

test('admin sees the invite action button', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/users')
            ->waitForText('Send invitation')
            ->assertSee('Send invitation');
    });
});

test('bare member does not see the invite action button', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create(['name' => 'Marge Member']);
    $member->joinOrganization($organization, OrganizationRole::Member);

    $this->browse(function (Browser $browser) use ($member, $organization): void {
        $browser->loginAs($member)
            ->visit('/'.$organization->slug.'/users')
            ->waitForText('Marge Member')
            ->assertDontSee('Send invitation');
    });
});
