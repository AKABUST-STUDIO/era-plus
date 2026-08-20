<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('admin can view the activity page', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['name' => 'Andy Admin']);
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    auth()->login($admin);
    ActivityLog::record($organization, 'Something happened');
    auth()->logout();

    $this->browse(function (Browser $browser) use ($admin, $organization): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/activity')
            ->waitForText('Something happened');
    });
});

test('bare member cannot see the activity page in sidebar', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);

    $this->browse(function (Browser $browser) use ($member, $organization): void {
        $browser->loginAs($member)
            ->visit('/'.$organization->slug.'/projects')
            ->waitForText('Projects')
            ->assertDontSee('Activity');
    });
});
