<?php

namespace Tests\Feature;

use App\Enums\Subscription\SubscriptionTier;
use App\Filament\Organization\Pages\Tenancy\CreateOrganization;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\FakesStripe;
use Tests\TestCase;

class CreateOrganizationWizardTest extends TestCase
{
    use FakesStripe;
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
        Livewire::test(CreateOrganization::class)->assertSuccessful();
    }

    public function test_register_persists_organization_and_attaches_user_as_admin(): void
    {
        Livewire::test(CreateOrganization::class)
            ->fillForm([
                'name' => 'Acme Nordic',
                'subscription_tier' => SubscriptionTier::Basic->value,
            ])
            ->call('register');

        $organization = Organization::query()->where('name', 'Acme Nordic')->firstOrFail();

        $this->assertTrue($organization->users()->whereKey($this->user->id)->exists());
        $this->assertTrue($this->user->fresh()->isOrgAdmin($organization));
    }

    public function test_register_attaches_the_free_project_subscription_item(): void
    {
        Livewire::test(CreateOrganization::class)
            ->fillForm([
                'name' => 'Free Slot Co',
                'subscription_tier' => SubscriptionTier::Basic->value,
            ])
            ->call('register');

        $organization = Organization::query()->where('name', 'Free Slot Co')->firstOrFail();

        $this->assertSame(1, $organization->freeProjectAllowance());
        $this->assertSame(1, $organization->projectLimit());
    }

    public function test_first_organization_becomes_default(): void
    {
        Livewire::test(CreateOrganization::class)
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

        Livewire::test(CreateOrganization::class)
            ->fillForm(['name' => 'Second Org', 'subscription_tier' => SubscriptionTier::Basic->value])
            ->call('register');

        $this->assertSame($existing->id, $this->user->fresh()->default_organization_id);
    }

    public function test_name_is_required(): void
    {
        Livewire::test(CreateOrganization::class)
            ->fillForm(['name' => null])
            ->call('register')
            ->assertHasFormErrors(['name']);
    }
}
