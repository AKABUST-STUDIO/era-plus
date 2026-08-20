<?php

use App\Filament\Organization\Pages\Tenancy\CreateOrganization;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Concerns\FakesStripe;

uses(FakesStripe::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
});

test('wizard renders', function (): void {
    Livewire::test(CreateOrganization::class)->assertSuccessful();
});

test('register persists organization and attaches user as admin', function (): void {
    Livewire::test(CreateOrganization::class)
        ->fillForm(['name' => 'Acme Nordic'])
        ->call('register');

    $organization = Organization::query()->where('name', 'Acme Nordic')->firstOrFail();

    $this->assertTrue($organization->users()->whereKey($this->user->id)->exists());
    $this->assertTrue($this->user->fresh()->isOrgAdmin($organization));
});

test('first organization becomes default', function (): void {
    Livewire::test(CreateOrganization::class)
        ->fillForm(['name' => 'First Org'])
        ->call('register');

    $organization = Organization::query()->where('name', 'First Org')->firstOrFail();

    $this->assertSame($organization->id, $this->user->fresh()->default_organization_id);
});

test('existing default is not overwritten', function (): void {
    $existing = Organization::factory()->create(['name' => 'Old']);
    $this->user->joinOrganization($existing);
    $this->user->update(['default_organization_id' => $existing->id]);

    Livewire::test(CreateOrganization::class)
        ->fillForm(['name' => 'Second Org'])
        ->call('register');

    $this->assertSame($existing->id, $this->user->fresh()->default_organization_id);
});

test('name is required', function (): void {
    Livewire::test(CreateOrganization::class)
        ->fillForm(['name' => null])
        ->call('register')
        ->assertHasFormErrors(['name']);
});
