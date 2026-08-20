<?php

use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('organization'));
});

function oauthProviderUrl(string $provider): string
{
    return route('auth.oauth.redirect', ['provider' => $provider]);
}

test('login page renders only providers that have credentials', function (): void {
    config([
        'services.google.client_id' => 'google-id',
        'services.microsoft.client_id' => 'ms-id',
        'services.apple.client_id' => null,
    ]);

    $response = $this->get(Filament::getLoginUrl());

    $response->assertSuccessful();
    $response->assertSee(oauthProviderUrl('google'));
    $response->assertSee(oauthProviderUrl('microsoft'));
    $response->assertDontSee(oauthProviderUrl('apple'));
    $response->assertSee('fi-btn', false);
    $response->assertDontSee('hover:bg-gray-50', false);
});

test('an unconfigured provider is omitted rather than rendered disabled', function (): void {
    config([
        'services.google.client_id' => 'google-id',
        'services.microsoft.client_id' => null,
        'services.apple.client_id' => null,
    ]);

    $response = $this->get(Filament::getLoginUrl());

    $response->assertSuccessful();
    $response->assertDontSee('Continue with Apple');
    $response->assertDontSee('Continue with Microsoft');
    $response->assertDontSee('fi-disabled', false);
});

test('register page renders only providers that have credentials', function (): void {
    config([
        'services.google.client_id' => 'google-id',
        'services.microsoft.client_id' => null,
        'services.apple.client_id' => null,
    ]);

    $response = $this->get(Filament::getRegistrationUrl());

    $response->assertSuccessful();
    $response->assertSee(oauthProviderUrl('google'));
    $response->assertDontSee(oauthProviderUrl('microsoft'));
    $response->assertDontSee(oauthProviderUrl('apple'));
    $response->assertSee('fi-btn', false);
    $response->assertDontSee('fi-disabled', false);
});

test('register page still renders the consent links', function (): void {
    $response = $this->get(Filament::getRegistrationUrl());

    $response->assertSuccessful();
    $response->assertSee('fi-auth-consent', false);
    $response->assertSee(route('legal.terms'));
    $response->assertSee(route('legal.privacy'));
});

test('no provider buttons render when no provider is configured', function (): void {
    config([
        'services.google.client_id' => null,
        'services.microsoft.client_id' => null,
        'services.apple.client_id' => null,
    ]);

    $response = $this->get(Filament::getLoginUrl());

    $response->assertSuccessful();
    $response->assertDontSee(oauthProviderUrl('google'));
    $response->assertDontSee(oauthProviderUrl('microsoft'));
    $response->assertDontSee(oauthProviderUrl('apple'));
});
