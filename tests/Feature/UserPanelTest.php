<?php

namespace Tests\Feature;

use App\Filament\User\Pages\Activity;
use App\Filament\User\Pages\BillingInformation;
use App\Filament\User\Pages\BillingItems;
use App\Filament\User\Pages\Invoices;
use App\Filament\User\Pages\Settings;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.test']);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    public function test_dashboard_loads_for_any_logged_in_user(): void
    {
        Livewire::test(Dashboard::class)->assertSuccessful();
    }

    public function test_settings_page_renders_with_user_data(): void
    {
        Livewire::test(Settings::class)
            ->assertSuccessful()
            ->assertFormSet(['name' => 'Maria']);
    }

    public function test_settings_can_update_name(): void
    {
        Livewire::test(Settings::class)
            ->fillForm(['name' => 'Anne'])
            ->call('saveProfile');

        $this->assertSame('Anne', $this->user->fresh()->name);
    }

    public function test_settings_can_set_default_organization(): void
    {
        $org = Organization::factory()->create();
        $this->user->joinOrganization($org);

        Livewire::test(Settings::class)
            ->fillForm(['default_organization_id' => $org->id])
            ->call('saveDefaultOrganization');

        $this->assertSame($org->id, $this->user->fresh()->default_organization_id);
    }

    public function test_each_wip_page_renders(): void
    {
        foreach ([Activity::class, BillingInformation::class, BillingItems::class, Invoices::class] as $page) {
            Livewire::test($page)->assertSuccessful();
        }
    }
}
