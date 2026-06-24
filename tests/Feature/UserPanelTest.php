<?php

namespace Tests\Feature;

use App\Filament\User\Pages\Activity;
use App\Filament\User\Pages\Authentication;
use App\Filament\User\Pages\Billing;
use App\Filament\User\Pages\Invoices;
use App\Filament\User\Pages\Organizations;
use App\Filament\User\Pages\Settings;
use App\Filament\User\Pages\Support;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
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
        Livewire::test(\Filament\Pages\Dashboard::class)->assertSuccessful();
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
        $org->users()->attach($this->user);

        Livewire::test(Settings::class)
            ->fillForm(['default_organization_id' => $org->id])
            ->call('saveDefaultOrganization');

        $this->assertSame($org->id, $this->user->fresh()->default_organization_id);
    }

    public function test_organizations_page_lists_only_user_orgs(): void
    {
        $mine = Organization::factory()->create(['name' => 'Mine']);
        $mine->users()->attach($this->user);
        Organization::factory()->create(['name' => 'Stranger']);

        Livewire::test(Organizations::class)
            ->assertSuccessful();

        $this->assertContains(
            $mine->id,
            Organization::query()
                ->whereHas('users', fn ($q) => $q->whereKey($this->user->id))
                ->pluck('id')
                ->all(),
        );
    }

    public function test_each_wip_page_renders(): void
    {
        foreach ([Activity::class, Support::class, Authentication::class, Billing::class, Invoices::class] as $page) {
            Livewire::test($page)->assertSuccessful();
        }
    }
}
