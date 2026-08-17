<?php

namespace Tests\Unit;

use App\Support\GoogleCalendarCredentials;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GoogleCalendarCredentialsTest extends TestCase
{
    public function test_is_configured_reads_credentials_from_services_config(): void
    {
        Config::set('services.google.credentials_b64', base64_encode('{"type":"service_account"}'));

        $this->assertTrue(GoogleCalendarCredentials::isConfigured());
    }

    public function test_is_configured_is_false_without_credentials(): void
    {
        Config::set('services.google.credentials_b64', null);

        $this->assertFalse(GoogleCalendarCredentials::isConfigured());
    }

    public function test_impersonate_reads_email_from_services_config(): void
    {
        Config::set('services.google.impersonate', 'owner@example.com');

        $this->assertSame('owner@example.com', GoogleCalendarCredentials::impersonate());
    }

    public function test_impersonate_is_null_without_email(): void
    {
        Config::set('services.google.impersonate', null);

        $this->assertNull(GoogleCalendarCredentials::impersonate());
    }
}
