<?php

namespace Tests\Feature;

use App\Enums\SubscriptionTier;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_footer_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.test']);
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\UserFooter::class)
            ->assertSee('Maria')
            ->assertSee('maria@example.test');
    }

    public function test_upgrade_cta_is_visible_for_free_tier_via_organization_service(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['subscription_tier' => SubscriptionTier::Free]);
        $organization->users()->attach($user);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);

        $this->assertTrue(\App\Facades\OrganizationService::shouldShowUpgradeCta());
    }

    public function test_upgrade_cta_is_hidden_for_pro_tier_via_organization_service(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['subscription_tier' => SubscriptionTier::Pro]);
        $organization->users()->attach($user);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);

        $this->assertFalse(\App\Facades\OrganizationService::shouldShowUpgradeCta());
    }
}
