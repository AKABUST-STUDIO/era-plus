<?php

namespace Tests\Feature;

use App\Filament\Organization\Settings\Pages\Invoices;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class InvoicesPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->user);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_invoices_page_renders(): void
    {
        Livewire::test(Invoices::class)->assertSuccessful();
    }

    public function test_empty_state_when_no_stripe_customer(): void
    {
        Livewire::test(Invoices::class)
            ->assertSee('No invoices yet');
    }

    public function test_returns_empty_rows_when_no_stripe_id(): void
    {
        $rows = Livewire::test(Invoices::class)
            ->instance()
            ->getInvoiceRows();

        $this->assertSame([], $rows);
    }
}
