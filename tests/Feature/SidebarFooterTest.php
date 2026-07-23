<?php

namespace Tests\Feature;

use App\Enums\Subscription\SubscriptionTier;
use App\Facades\OrganizationService;
use App\Livewire\UserFooter;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SidebarFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_footer_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.test']);
        $this->actingAs($user);

        Livewire::test(UserFooter::class)
            ->assertSee('Maria')
            ->assertSee('maria@example.test');
    }

    public function test_upgrade_cta_is_visible_for_free_tier_via_organization_service(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->joinOrganization($organization);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);

        $this->assertTrue(OrganizationService::shouldShowUpgradeCta());
    }

    public function test_upgrade_cta_is_hidden_for_pro_tier_via_organization_service(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->subscribed(SubscriptionTier::Pro)->create();
        $user->joinOrganization($organization);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);

        $this->assertFalse(OrganizationService::shouldShowUpgradeCta());
    }
}
