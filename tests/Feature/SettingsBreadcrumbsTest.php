<?php

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create(['name' => 'Acme Erasmus']);
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
    Filament::setTenant($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);
});

test('only settings panels render breadcrumbs', function (): void {
    $this->assertTrue(Filament::getPanel('organization.settings')->hasBreadcrumbs());
    $this->assertTrue(Filament::getPanel('project.settings')->hasBreadcrumbs());

    $this->assertFalse(Filament::getPanel('organization')->hasBreadcrumbs());
    $this->assertFalse(Filament::getPanel('project')->hasBreadcrumbs());
    $this->assertFalse(Filament::getPanel('user')->hasBreadcrumbs());
});

dataset('settings_breadcrumbs_pages', [
    'organization' => [OrganizationSettings::class, 'Organization'],
]);

dataset('settings_breadcrumbs_page_classes', [
    'organization' => [OrganizationSettings::class],
]);

test('breadcrumbs have three segments', function (string $page, string $expectedTitle): void {
    $breadcrumbs = Livewire::test($page)
        ->instance()
        ->getBreadcrumbs();

    $values = array_values($breadcrumbs);
    $this->assertCount(3, $values);
    $this->assertSame('Acme Erasmus', $values[0]);
    $this->assertSame('Settings', $values[1]);
    $this->assertSame($expectedTitle, $values[2]);
})->with('settings_breadcrumbs_pages');

test('organization segment is a link to org panel dashboard', function (string $page): void {
    $breadcrumbs = Livewire::test($page)
        ->instance()
        ->getBreadcrumbs();

    $orgUrl = array_key_first($breadcrumbs);

    $this->assertNotNull($orgUrl);
    $this->assertSame(
        $orgUrl,
        Filament::getPanel('organization')->getUrl(tenant: $this->organization)
    );
})->with('settings_breadcrumbs_page_classes');
