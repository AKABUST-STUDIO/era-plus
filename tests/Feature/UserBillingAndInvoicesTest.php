<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionTier;
use App\Facades\OrganizationService;
use App\Filament\User\Pages\Billing;
use App\Filament\User\Pages\Invoices;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserBillingAndInvoicesTest extends TestCase
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

    public function test_billing_page_lists_only_organizations_the_user_pays_for(): void
    {
        $owned = Organization::factory()->subscribed(SubscriptionTier::Pro, $this->user)->create(['name' => 'Owned']);

        $member = Organization::factory()->create(['name' => 'Member']);
        $this->user->joinOrganization($member, OrganizationRole::Admin);

        Organization::factory()->subscribed(SubscriptionTier::Pro)->create(['name' => 'Stranger']);

        $orgs = Livewire::test(Billing::class)
            ->instance()
            ->getOwnedOrganizations();

        $this->assertCount(1, $orgs);
        $this->assertTrue($orgs->first()->is($owned));
    }

    public function test_billing_empty_state_for_user_without_a_subscription(): void
    {
        Livewire::test(Billing::class)
            ->assertSee(__('user.billing.empty'));
    }

    public function test_billing_shows_card_label_when_payment_method_present(): void
    {
        $this->user->forceFill(['pm_type' => 'visa', 'pm_last_four' => '4242'])->save();

        $this->assertSame('Visa …4242', (new Billing)->cardLabel());
    }

    public function test_billing_shows_no_payment_message_when_card_missing(): void
    {
        $this->assertSame(__('user.billing.no_payment_method'), (new Billing)->cardLabel());
    }

    public function test_invoices_page_renders_empty_state_without_billing(): void
    {
        Livewire::test(Invoices::class)
            ->assertSee(__('user.invoices.empty_no_invoices'));
    }

    public function test_invoices_returns_empty_collection_without_stripe_id(): void
    {
        $rows = Livewire::test(Invoices::class)
            ->instance()
            ->getInvoiceRows();

        $this->assertTrue($rows->isEmpty());
    }

    public function test_organization_service_should_show_upgrade_cta_returns_false_when_no_tenant(): void
    {
        $this->assertFalse(OrganizationService::shouldShowUpgradeCta());
    }
}
