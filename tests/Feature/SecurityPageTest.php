<?php

namespace Tests\Feature;

use App\Filament\Organization\Settings\Pages\Security;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->user->joinOrganization($this->organization);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_page_renders(): void
    {
        Livewire::test(Security::class)->assertSuccessful();
    }

    public function test_can_enable_two_factor_enforcement(): void
    {
        Livewire::test(Security::class)
            ->fillForm(['enforce_two_factor' => true])
            ->call('saveTwoFactor');

        $this->assertTrue((bool) $this->organization->fresh()->enforce_two_factor);
    }

    public function test_can_enable_email_verification_enforcement(): void
    {
        Livewire::test(Security::class)
            ->fillForm(['enforce_email_verification' => true])
            ->call('saveEmailVerification');

        $this->assertTrue((bool) $this->organization->fresh()->enforce_email_verification);
    }

    public function test_changes_are_recorded_in_activity_log(): void
    {
        Livewire::test(Security::class)
            ->fillForm(['enforce_two_factor' => true])
            ->call('saveTwoFactor');

        $this->assertDatabaseHas('activity_log', [
            'organization_id' => $this->organization->id,
            'event' => 'organization.security.two_factor',
        ]);
    }
}
