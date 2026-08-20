<?php

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Settings\Pages\Invoices;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->markTestSkipped('Invoices page is intentionally hidden.');

    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
    Filament::setTenant($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);
});

test('invoices page renders', function (): void {
    Livewire::test(Invoices::class)->assertSuccessful();
});

test('empty state when no stripe customer', function (): void {
    Livewire::test(Invoices::class)
        ->assertSee('No invoices yet');
});

test('returns empty rows when no stripe id', function (): void {
    $rows = Livewire::test(Invoices::class)
        ->instance()
        ->getInvoiceRows();

    $this->assertSame([], $rows);
});
