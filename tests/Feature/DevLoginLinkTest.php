<?php

use App\Models\User;
use Spatie\LoginLink\Exceptions\DidNotFindUserToLogIn;
use Spatie\LoginLink\Exceptions\NotAllowedInCurrentEnvironment;

beforeEach(function (): void {
    config()->set('login-link.allowed_environments', ['local', 'testing']);
});

test('developer login signs in an existing user by email', function (): void {
    $user = User::factory()->create();

    $this->post(route('loginLinkLogin'), ['email' => $user->email])
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

test('developer login does not create a user that does not exist', function (): void {
    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('loginLinkLogin'), ['email' => 'nobody@era-plus.test']))
        ->toThrow(DidNotFindUserToLogIn::class);

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('developer login is refused outside the allowed environments', function (): void {
    config()->set('login-link.allowed_environments', ['local']);

    $user = User::factory()->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('loginLinkLogin'), ['email' => $user->email]))
        ->toThrow(NotAllowedInCurrentEnvironment::class);

    $this->assertGuest();
});

test('the developer login block lists a login link per user', function (): void {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);

    $html = view('filament.auth.dev-login', ['users' => collect([$user])])->render();

    expect($html)
        ->toContain('Ada Lovelace · '.$user->email)
        ->toContain('value="'.$user->email.'"')
        ->toContain(route('loginLinkLogin'));
});

test('the developer login block handles an empty database', function (): void {
    $html = view('filament.auth.dev-login', ['users' => collect()])->render();

    expect($html)->toContain('No users in the database yet.');
});
