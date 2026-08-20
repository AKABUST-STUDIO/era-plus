<?php

use App\Support\GoogleCalendarCredentials;
use Illuminate\Support\Facades\Config;

test('is configured reads credentials from services config', function (): void {
    Config::set('services.google.credentials_b64', base64_encode('{"type":"service_account"}'));

    expect(GoogleCalendarCredentials::isConfigured())->toBeTrue();
});

test('is configured is false without credentials', function (): void {
    Config::set('services.google.credentials_b64', null);

    expect(GoogleCalendarCredentials::isConfigured())->toBeFalse();
});

test('impersonate reads email from services config', function (): void {
    Config::set('services.google.impersonate', 'owner@example.com');

    expect(GoogleCalendarCredentials::impersonate())->toBe('owner@example.com');
});

test('impersonate is null without email', function (): void {
    Config::set('services.google.impersonate', null);

    expect(GoogleCalendarCredentials::impersonate())->toBeNull();
});
