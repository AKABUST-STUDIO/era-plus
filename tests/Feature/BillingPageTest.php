<?php

namespace Tests\Feature;

use App\Enums\Subscription\SubscriptionTier;
use App\Filament\Organization\Settings\Pages\Billing;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->user->joinOrganization($this->organization);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_billing_page_renders(): void
    {
        Livewire::test(Billing::class)
            ->assertSuccessful();
    }

    public function test_billing_page_shows_basic_pitch_for_basic_tier(): void
    {
        Livewire::test(Billing::class)
            ->assertSee(__('settings.billing.plan.basic_pitch'));
    }

    public function test_billing_page_shows_pro_message_for_pro_tier(): void
    {
        $this->organization->update(['subscription_tier' => SubscriptionTier::Pro]);

        Livewire::test(Billing::class)
            ->assertSee(__('settings.billing.plan.pro_active'))
            ->assertDontSee(__('settings.billing.plan.basic_pitch'));
    }

    public function test_can_save_billing_address(): void
    {
        Livewire::test(Billing::class)
            ->fillForm([
                'address_line1' => 'Pikk 1',
                'address_city' => 'Tallinn',
                'address_postal_code' => '10101',
                'address_country' => 'EE',
            ])
            ->call('saveAddress');

        $this->assertSame(
            [
                'line1' => 'Pikk 1',
                'line2' => null,
                'city' => 'Tallinn',
                'state' => null,
                'postal_code' => '10101',
                'country' => 'EE',
            ],
            $this->organization->fresh()->billing_address,
        );
    }

    public function test_can_save_invoice_language(): void
    {
        Livewire::test(Billing::class)
            ->fillForm(['invoice_language' => 'et'])
            ->call('saveLanguage');

        $this->assertSame('et', $this->organization->fresh()->invoice_language);
    }

    public function test_can_save_tax_id(): void
    {
        Livewire::test(Billing::class)
            ->fillForm(['tax_id' => 'EE123456789'])
            ->call('saveTaxId');

        $this->assertSame('EE123456789', $this->organization->fresh()->tax_id);
    }
}
