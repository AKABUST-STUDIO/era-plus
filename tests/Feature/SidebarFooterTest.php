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

    public function test_user_menu_includes_account_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));

        $this->assertContains('Your account', $this->menuItemLabels());
    }

    public function test_upgrade_cta_is_visible_for_free_tier(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['subscription_tier' => SubscriptionTier::Free]);
        $organization->users()->attach($user);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);

        $this->assertContains('Upgrade to Pro', $this->menuItemLabels());
    }

    public function test_upgrade_cta_is_hidden_for_pro_tier(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['subscription_tier' => SubscriptionTier::Pro]);
        $organization->users()->attach($user);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);

        $this->assertNotContains('Upgrade to Pro', $this->menuItemLabels());
    }

    /**
     * @return array<int, string>
     */
    private function menuItemLabels(): array
    {
        return collect(Filament::getPanel('organization')->getUserMenuItems())
            ->map(fn (object $item): string => (string) $item->getLabel())
            ->all();
    }
}
