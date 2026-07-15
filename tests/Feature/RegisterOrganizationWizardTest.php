<?php

namespace Tests\Feature;

use App\Enums\SubscriptionTier;
use App\Filament\Organization\Pages\Tenancy\RegisterOrganization;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterOrganizationWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
    }

    public function test_wizard_renders(): void
    {
        Livewire::test(RegisterOrganization::class)->assertSuccessful();
    }

    public function test_register_persists_organization_and_attaches_user_as_admin(): void
    {
        Livewire::test(RegisterOrganization::class)
            ->fillForm([
                'name' => 'Acme Nordic',
                'subscription_tier' => SubscriptionTier::Basic->value,
            ])
            ->call('register');

        $organization = Organization::query()->where('name', 'Acme Nordic')->firstOrFail();

        $this->assertTrue($organization->users()->whereKey($this->user->id)->exists());
        $this->assertTrue($this->user->fresh()->isOrgAdmin($organization));
    }

    public function test_first_organization_becomes_default(): void
    {
        Livewire::test(RegisterOrganization::class)
            ->fillForm(['name' => 'First Org', 'subscription_tier' => SubscriptionTier::Basic->value])
            ->call('register');

        $organization = Organization::query()->where('name', 'First Org')->firstOrFail();

        $this->assertSame($organization->id, $this->user->fresh()->default_organization_id);
    }

    public function test_existing_default_is_not_overwritten(): void
    {
        $existing = Organization::factory()->create(['name' => 'Old']);
        $this->user->joinOrganization($existing);
        $this->user->update(['default_organization_id' => $existing->id]);

        Livewire::test(RegisterOrganization::class)
            ->fillForm(['name' => 'Second Org', 'subscription_tier' => SubscriptionTier::Basic->value])
            ->call('register');

        $this->assertSame($existing->id, $this->user->fresh()->default_organization_id);
    }

    public function test_name_is_required(): void
    {
        Livewire::test(RegisterOrganization::class)
            ->fillForm(['name' => null])
            ->call('register')
            ->assertHasFormErrors(['name']);
    }
}
