<?php

namespace Tests\Feature;

use App\Facades\OrganizationService;
use App\Filament\Organization\Pages\Overview;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsBackButtonTest extends TestCase
{
    use RefreshDatabase;

    private function render(): string
    {
        return view('filament.user.components.back')->render();
    }

    public function test_it_links_back_to_the_organization_overview(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->joinOrganization($organization);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
        OrganizationService::remember($organization);

        $html = $this->render();

        $this->assertStringContainsString(__('navigation.back'), $html);
        $this->assertStringContainsString(
            Overview::getUrl(panel: 'organization', tenant: $organization),
            $html,
        );
    }

    public function test_it_renders_nothing_without_a_current_organization(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
        OrganizationService::forget();

        $this->assertSame('', trim($this->render()));
    }
}
