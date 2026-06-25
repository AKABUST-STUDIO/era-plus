<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnforcementMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = 'app.'.parse_url(config('app.url'), PHP_URL_HOST);
    }

    private function attachAdmin(Organization $organization, User $user): void
    {
        $organization->users()->attach($user, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);
    }

    public function test_two_factor_enforcement_redirects_unverified_user(): void
    {
        $organization = Organization::factory()->create([
            'enforce_two_factor' => true,
        ]);
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);
        $this->attachAdmin($organization, $user);

        $response = $this->actingAs($user)
            ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general');

        $response->assertRedirect();
        $this->assertStringContainsString('two-factor-required', $response->headers->get('Location'));
    }

    public function test_two_factor_enforcement_allows_user_with_2fa(): void
    {
        $organization = Organization::factory()->create([
            'enforce_two_factor' => true,
        ]);
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $this->attachAdmin($organization, $user);

        $this->actingAs($user)
            ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general')
            ->assertSuccessful();
    }

    public function test_two_factor_enforcement_skipped_when_org_does_not_enforce(): void
    {
        $organization = Organization::factory()->create([
            'enforce_two_factor' => false,
        ]);
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);
        $this->attachAdmin($organization, $user);

        $this->actingAs($user)
            ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general')
            ->assertSuccessful();
    }

    public function test_two_factor_required_page_renders_when_enforced(): void
    {
        $organization = Organization::factory()->create([
            'enforce_two_factor' => true,
        ]);
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);
        $this->attachAdmin($organization, $user);

        $this->actingAs($user)
            ->get('http://'.$this->host.'/'.$organization->slug.'/settings/two-factor-required')
            ->assertSuccessful()
            ->assertSee($organization->name);
    }

    public function test_email_verification_enforcement_redirects_unverified_user(): void
    {
        $organization = Organization::factory()->create([
            'enforce_email_verification' => true,
        ]);
        $user = User::factory()->unverified()->create();
        $this->attachAdmin($organization, $user);

        $response = $this->actingAs($user)
            ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general');

        $response->assertRedirect();
        $this->assertStringContainsString('email-verification-required', $response->headers->get('Location'));
    }

    public function test_email_verification_enforcement_allows_verified_user(): void
    {
        $organization = Organization::factory()->create([
            'enforce_email_verification' => true,
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->attachAdmin($organization, $user);

        $this->actingAs($user)
            ->get('http://'.$this->host.'/'.$organization->slug.'/settings/general')
            ->assertSuccessful();
    }

    public function test_email_verification_required_page_renders_when_enforced(): void
    {
        $organization = Organization::factory()->create([
            'enforce_email_verification' => true,
        ]);
        $user = User::factory()->unverified()->create();
        $this->attachAdmin($organization, $user);

        $this->actingAs($user)
            ->get('http://'.$this->host.'/'.$organization->slug.'/settings/email-verification-required')
            ->assertSuccessful()
            ->assertSee($organization->name);
    }
}
