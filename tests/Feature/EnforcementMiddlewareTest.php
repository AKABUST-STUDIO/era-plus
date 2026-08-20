<?php

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

beforeEach(function (): void {
    $this->host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);
});

function attachAdmin(Organization $organization, User $user): void
{
    $user->joinOrganization($organization, OrganizationRole::Admin);
}

test('two factor enforcement redirects unverified user', function (): void {
    $organization = Organization::factory()->create([
        'enforce_two_factor' => true,
    ]);
    $user = User::factory()->create(['two_factor_confirmed_at' => null]);
    attachAdmin($organization, $user);

    $response = $this->actingAs($user)
        ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general');

    $response->assertRedirect();
    $this->assertStringContainsString('two-factor-required', $response->headers->get('Location'));
});

test('two factor enforcement allows user with 2fa', function (): void {
    $organization = Organization::factory()->create([
        'enforce_two_factor' => true,
    ]);
    $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
    attachAdmin($organization, $user);

    $this->actingAs($user)
        ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general')
        ->assertSuccessful();
});

test('two factor enforcement skipped when org does not enforce', function (): void {
    $organization = Organization::factory()->create([
        'enforce_two_factor' => false,
    ]);
    $user = User::factory()->create(['two_factor_confirmed_at' => null]);
    attachAdmin($organization, $user);

    $this->actingAs($user)
        ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general')
        ->assertSuccessful();
});

test('two factor required page renders when enforced', function (): void {
    $organization = Organization::factory()->create([
        'enforce_two_factor' => true,
    ]);
    $user = User::factory()->create(['two_factor_confirmed_at' => null]);
    attachAdmin($organization, $user);

    $this->actingAs($user)
        ->get('http://'.$this->host.'/'.$organization->slug.'/settings/two-factor-required')
        ->assertSuccessful()
        ->assertSee($organization->name);
});

test('email verification enforcement redirects unverified user', function (): void {
    $organization = Organization::factory()->create([
        'enforce_email_verification' => true,
    ]);
    $user = User::factory()->unverified()->create();
    attachAdmin($organization, $user);

    $response = $this->actingAs($user)
        ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general');

    $response->assertRedirect();
    $this->assertStringContainsString('email-verification-required', $response->headers->get('Location'));
});

test('email verification enforcement allows verified user', function (): void {
    $organization = Organization::factory()->create([
        'enforce_email_verification' => true,
    ]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    attachAdmin($organization, $user);

    $this->actingAs($user)
        ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general')
        ->assertSuccessful();
});

test('email verification required page renders when enforced', function (): void {
    $organization = Organization::factory()->create([
        'enforce_email_verification' => true,
    ]);
    $user = User::factory()->unverified()->create();
    attachAdmin($organization, $user);

    $this->actingAs($user)
        ->get('http://'.$this->host.'/'.$organization->slug.'/settings/email-verification-required')
        ->assertSuccessful()
        ->assertSee($organization->name);
});
