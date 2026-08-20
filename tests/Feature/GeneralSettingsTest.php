<?php

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function actingOnSettingsPanel($test, User $user, Organization $organization): void
{
    $test->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    Filament::setTenant($organization);
    OrganizationService::remember($organization);
    URL::defaults(['organization' => $organization->slug]);
}

/**
 * @return array{0: User, 1: Organization}
 */
function memberOfOrganization(OrganizationRole $role = OrganizationRole::Admin): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization, $role);

    return [$user, $organization];
}

test('page loads with the current organization', function (): void {
    [$user, $organization] = memberOfOrganization();
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'name' => $organization->name,
            'slug' => $organization->slug,
        ]);
});

test('admin can update the organization name', function (): void {
    [$user, $organization] = memberOfOrganization();
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->fillForm(['name' => 'Renamed Organization'])
        ->callAction(TestAction::make('saveName')->schemaComponent('name-section', 'form'))
        ->assertNotified()
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('organizations', [
        'id' => $organization->id,
        'name' => 'Renamed Organization',
    ]);
});

test('admin can update the organization url', function (): void {
    [$user, $organization] = memberOfOrganization();
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->fillForm(['slug' => 'renamed-org'])
        ->callAction(TestAction::make('saveUrl')->schemaComponent('url-section', 'form'))
        ->assertNotified()
        ->assertRedirect(OrganizationSettings::getUrl(['organization' => 'renamed-org']));

    $this->assertDatabaseHas('organizations', [
        'id' => $organization->id,
        'slug' => 'renamed-org',
    ]);
});

test('organization url must be unique', function (): void {
    [$user, $organization] = memberOfOrganization();
    Organization::factory()->create(['name' => 'Taken', 'slug' => 'taken']);
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->fillForm(['slug' => 'taken'])
        ->callAction(TestAction::make('saveUrl')->schemaComponent('url-section', 'form'))
        ->assertHasFormErrors(['slug']);

    $this->assertDatabaseHas('organizations', [
        'id' => $organization->id,
        'slug' => $organization->slug,
    ]);
});

test('organization url is required', function (): void {
    [$user, $organization] = memberOfOrganization();
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->fillForm(['slug' => ''])
        ->callAction(TestAction::make('saveUrl')->schemaComponent('url-section', 'form'))
        ->assertHasFormErrors(['slug' => 'required']);
});

test('admin can upload an avatar', function (): void {
    Storage::fake('public');
    [$user, $organization] = memberOfOrganization();
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->fillForm(['avatar' => [UploadedFile::fake()->image('avatar.png')]])
        ->callAction(TestAction::make('saveAvatar')->schemaComponent('avatar-section', 'form'))
        ->assertNotified();

    $this->assertNotNull($organization->fresh()->getFirstMedia('avatar'));
});

test('admin can leave an organization with another admin', function (): void {
    [$user, $organization] = memberOfOrganization();
    User::factory()->create()->joinOrganization($organization, OrganizationRole::Admin);
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->callAction(TestAction::make('leave')->schemaComponent('leave-section', 'form'))
        ->assertNotified()
        ->assertRedirect();

    $this->assertFalse($organization->fresh()->users->contains($user));
});

test('sole admin cannot leave the organization', function (): void {
    [$user, $organization] = memberOfOrganization();
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->callAction(TestAction::make('leave')->schemaComponent('leave-section', 'form'));

    $this->assertTrue($organization->fresh()->users->contains($user));
});

test('admin can delete the organization', function (): void {
    [$user, $organization] = memberOfOrganization();
    actingOnSettingsPanel($this, $user, $organization);

    Livewire::test(OrganizationSettings::class)
        ->callAction(TestAction::make('delete')->schemaComponent('delete-section', 'form'), data: [
            'name_confirm' => $organization->name,
            'phrase_confirm' => __('settings.general.delete.confirm_phrase'),
        ])
        ->assertNotified()
        ->assertRedirect();

    $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
});
