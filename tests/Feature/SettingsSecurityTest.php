<?php

namespace Tests\Feature;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\Security;
use App\Models\Organization;
use App\Models\User;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_loads_with_the_two_factor_section(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->joinOrganization($organization);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel(SettingsPanelProvider::PANEL_ID));
        OrganizationService::remember($organization);

        Livewire::test(Security::class)
            ->assertOk()
            ->assertSee(__('settings.security.two_factor.heading'));
    }
}
