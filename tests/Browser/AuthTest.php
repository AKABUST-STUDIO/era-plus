<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('unknown email shows the otp verification step', function (): void {
    $this->browse(function (Browser $browser): void {
        $browser->visit('/login')
            ->waitFor('input[type="email"]')
            ->type('input[type="email"]', 'nobody@test.local')
            ->press('Continue with email')
            ->waitForText('Check your email')
            ->assertSee('Check your email');
    });
});

test('authenticated user lands on the org panel', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['email' => 'dusk-auth@test.local']);
    $user->joinOrganization($organization, OrganizationRole::Admin);

    $this->browse(function (Browser $browser) use ($user, $organization): void {
        $browser->loginAs($user)
            ->visit('/'.$organization->slug.'/projects')
            ->waitForText('Projects')
            ->assertPathIs('/'.$organization->slug.'/projects')
            ->assertSee('Projects');
    });
});

test('unauthenticated user is redirected to the login page', function (): void {
    $organization = Organization::factory()->create();

    $this->browse(function (Browser $browser) use ($organization): void {
        $browser->visit('/'.$organization->slug.'/projects');
        $browser->pause(500);

        expect($browser->driver->getCurrentURL())->toContain('/login');
    });
});
