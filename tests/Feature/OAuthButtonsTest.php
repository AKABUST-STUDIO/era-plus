<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthButtonsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('organization'));
    }

    private function providerUrl(string $provider): string
    {
        return route('auth.oauth.redirect', ['provider' => $provider]);
    }

    public function test_login_page_renders_only_providers_that_have_credentials(): void
    {
        config([
            'services.google.client_id' => 'google-id',
            'services.microsoft.client_id' => 'ms-id',
            'services.apple.client_id' => null,
        ]);

        $response = $this->get(Filament::getLoginUrl());

        $response->assertSuccessful();
        $response->assertSee($this->providerUrl('google'));
        $response->assertSee($this->providerUrl('microsoft'));
        $response->assertDontSee($this->providerUrl('apple'));
        $response->assertSee('fi-btn', false);
        $response->assertDontSee('hover:bg-gray-50', false);
    }

    public function test_an_unconfigured_provider_is_omitted_rather_than_rendered_disabled(): void
    {
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
    }

    public function test_register_page_renders_only_providers_that_have_credentials(): void
    {
        config([
            'services.google.client_id' => 'google-id',
            'services.microsoft.client_id' => null,
            'services.apple.client_id' => null,
        ]);

        $response = $this->get(Filament::getRegistrationUrl());

        $response->assertSuccessful();
        $response->assertSee($this->providerUrl('google'));
        $response->assertDontSee($this->providerUrl('microsoft'));
        $response->assertDontSee($this->providerUrl('apple'));
        $response->assertSee('fi-btn', false);
        $response->assertDontSee('fi-disabled', false);
    }

    public function test_register_page_still_renders_the_consent_links(): void
    {
        $response = $this->get(Filament::getRegistrationUrl());

        $response->assertSuccessful();
        $response->assertSee('fi-auth-consent', false);
        $response->assertSee(route('legal.terms'));
        $response->assertSee(route('legal.privacy'));
    }

    public function test_no_provider_buttons_render_when_no_provider_is_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.microsoft.client_id' => null,
            'services.apple.client_id' => null,
        ]);

        $response = $this->get(Filament::getLoginUrl());

        $response->assertSuccessful();
        $response->assertDontSee($this->providerUrl('google'));
        $response->assertDontSee($this->providerUrl('microsoft'));
        $response->assertDontSee($this->providerUrl('apple'));
    }
}
