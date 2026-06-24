<?php

namespace Tests\Feature;

use App\Enums\SubscriptionTier;
use App\Filament\User\Pages\CreateOrganization;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateOrganizationWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    public function test_wizard_renders(): void
    {
        Livewire::test(CreateOrganization::class)->assertSuccessful();
    }

    public function test_create_persists_organization_and_attaches_user(): void
    {
        Livewire::test(CreateOrganization::class)
            ->fillForm([
                'name' => 'Acme Nordic',
                'subscription_tier' => SubscriptionTier::Pro->value,
            ])
            ->call('create');

        $organization = Organization::query()->where('name', 'Acme Nordic')->firstOrFail();

        $this->assertSame(SubscriptionTier::Pro, $organization->subscription_tier);
        $this->assertTrue($organization->users()->whereKey($this->user->id)->exists());
    }

    public function test_first_organization_becomes_default(): void
    {
        Livewire::test(CreateOrganization::class)
            ->fillForm(['name' => 'First Org', 'subscription_tier' => SubscriptionTier::Free->value])
            ->call('create');

        $organization = Organization::query()->where('name', 'First Org')->firstOrFail();

        $this->assertSame($organization->id, $this->user->fresh()->default_organization_id);
    }

    public function test_existing_default_is_not_overwritten(): void
    {
        $existing = Organization::factory()->create(['name' => 'Old']);
        $existing->users()->attach($this->user);
        $this->user->update(['default_organization_id' => $existing->id]);

        Livewire::test(CreateOrganization::class)
            ->fillForm(['name' => 'Second Org', 'subscription_tier' => SubscriptionTier::Free->value])
            ->call('create');

        $this->assertSame($existing->id, $this->user->fresh()->default_organization_id);
    }

    public function test_name_is_required(): void
    {
        Livewire::test(CreateOrganization::class)
            ->fillForm(['name' => null])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }
}
