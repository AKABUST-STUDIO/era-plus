<?php

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function lifecycleActOnSettingsPanel(object $testCase, User $user, Organization $organization): void
{
    $testCase->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
    Filament::setTenant($organization);
    URL::defaults(['organization' => $organization->slug]);
}

test('leave action is disabled when user is only admin', function (): void {
    $admin = User::factory()->create();
    $organization = Organization::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);
    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);

    lifecycleActOnSettingsPanel($this, $admin, $organization);

    Livewire::test(OrganizationSettings::class)->callAction(TestAction::make('leave')->schemaComponent('leave-section', 'form'));

    $this->assertTrue($organization->fresh()->users->contains($admin));
});

test('leave allowed when there are other admins', function (): void {
    $admin = User::factory()->create();
    $coAdmin = User::factory()->create();
    $organization = Organization::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);
    $coAdmin->joinOrganization($organization, OrganizationRole::Admin);

    lifecycleActOnSettingsPanel($this, $admin, $organization);

    Livewire::test(OrganizationSettings::class)->callAction(TestAction::make('leave')->schemaComponent('leave-section', 'form'));

    $this->assertFalse($organization->fresh()->users->contains($admin));
});

test('delete modal form includes name and phrase inputs', function (): void {
    $admin = User::factory()->create();
    $organization = Organization::factory()->create(['name' => 'Acme Corp']);
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    lifecycleActOnSettingsPanel($this, $admin, $organization);

    $instance = Livewire::test(OrganizationSettings::class)->instance();

    $action = collect($instance->form(Schema::make($instance))->getComponents(withHidden: true))
        ->flatMap(fn ($component) => method_exists($component, 'getFooterActions') ? $component->getFooterActions() : [])
        ->firstWhere(fn ($a) => $a->getName() === 'delete');

    $this->assertNotNull($action, 'Delete action not found in any section footer.');

    $schema = Schema::make($instance);
    $fields = collect($action->getForm($schema)->getComponents())
        ->map(fn ($c) => $c->getName())
        ->all();

    $this->assertContains('name_confirm', $fields);
    $this->assertContains('phrase_confirm', $fields);
});

test('delete method soft deletes organization', function (): void {
    $admin = User::factory()->create();
    $organization = Organization::factory()->create(['name' => 'Acme Corp']);
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    lifecycleActOnSettingsPanel($this, $admin, $organization);

    Livewire::test(OrganizationSettings::class)
        ->callAction(TestAction::make('delete')->schemaComponent('delete-section', 'form'), data: [
            'name_confirm' => 'Acme Corp',
            'phrase_confirm' => __('settings.general.delete.confirm_phrase'),
        ]);

    $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
});
