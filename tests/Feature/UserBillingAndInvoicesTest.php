<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
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

    public function test_billing_page_lists_only_owned_organizations(): void
    {
        $owned = Organization::factory()->create(['name' => 'Owned']);
        $owned->users()->attach($this->user, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);

        $member = Organization::factory()->create(['name' => 'Member']);
        $member->users()->attach($this->user, [
            'role' => OrganizationRole::Member->value,
            'is_admin' => false,
        ]);

        $stranger = Organization::factory()->create(['name' => 'Stranger']);

        $orgs = Livewire::test(Billing::class)
            ->instance()
            ->getOwnedOrganizations();

        $this->assertCount(1, $orgs);
        $this->assertTrue($orgs->first()->is($owned));
    }

    public function test_billing_empty_state_for_user_with_no_owned_orgs(): void
    {
        Livewire::test(Billing::class)
            ->assertSee(__('user.billing.empty'));
    }

    public function test_billing_shows_card_label_when_payment_method_present(): void
    {
        $organization = Organization::factory()->create([
            'pm_type' => 'visa',
            'pm_last_four' => '4242',
        ]);
        $organization->users()->attach($this->user, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);

        $label = (new Billing)->cardLabel($organization);

        $this->assertSame('Visa …4242', $label);
    }

    public function test_billing_shows_no_payment_message_when_card_missing(): void
    {
        $organization = Organization::factory()->create([
            'pm_type' => null,
            'pm_last_four' => null,
        ]);

        $label = (new Billing)->cardLabel($organization);

        $this->assertSame(__('user.billing.no_payment_method'), $label);
    }

    public function test_invoices_page_renders_no_orgs_empty_state(): void
    {
        Livewire::test(Invoices::class)
            ->assertSee(__('user.invoices.empty_no_orgs'));
    }

    public function test_invoices_returns_empty_collection_without_stripe_id(): void
    {
        $organization = Organization::factory()->create();
        $organization->users()->attach($this->user, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);

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
