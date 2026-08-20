<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('admin can view the roles page', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/settings/roles')
            ->waitForText('Admin')
            ->assertSee('Admin')
            ->assertSee('Member');
    });
});

test('member cannot view the roles page', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);

    $this->browse(function (Browser $browser) use ($member, $organization): void {
        $browser->loginAs($member)
            ->visit('/'.$organization->slug.'/settings/roles');
        $browser->pause(500);

        $body = (string) $browser->script('return document.body ? document.body.innerText : "";')[0];
        expect($body)->toMatch('/Forbidden|Not Found|403|404/');
    });
});
