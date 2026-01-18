<?php

namespace Tests\Feature;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\GeneralSettings;
use App\Models\Organization;
use App\Models\User;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function actingOnSettingsPanel(User $user, Organization $organization): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel(SettingsPanelProvider::PANEL_ID));
        OrganizationService::remember($organization);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function memberOfOrganization(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user);

        return [$user, $organization];
    }

    public function test_page_loads_with_the_current_organization(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->assertOk()
            ->assertSchemaStateSet([
                'name' => $organization->name,
                'slug' => $organization->slug,
            ]);
    }

    public function test_member_can_update_the_organization_name(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->fillForm(['name' => 'Renamed Organization'])
            ->call('saveName')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Renamed Organization',
        ]);
    }

    public function test_member_can_update_the_organization_url(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->fillForm(['slug' => 'renamed-org'])
            ->call('saveUrl')
            ->assertNotified()
            ->assertRedirect(GeneralSettings::getUrl(['organization' => 'renamed-org']));

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'slug' => 'renamed-org',
        ]);
    }

    public function test_organization_url_must_be_unique(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        Organization::factory()->create(['name' => 'Taken', 'slug' => 'taken']);
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->fillForm(['slug' => 'taken'])
            ->call('saveUrl')
            ->assertHasFormErrors(['slug']);

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'slug' => $organization->slug,
        ]);
    }

    public function test_organization_url_is_required(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->fillForm(['slug' => ''])
            ->call('saveUrl')
            ->assertHasFormErrors(['slug' => 'required']);
    }

    public function test_member_can_upload_an_avatar(): void
    {
        Storage::fake('public');
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->fillForm(['avatar' => [UploadedFile::fake()->image('avatar.png')]])
            ->call('saveAvatar')
            ->assertNotified();

        $this->assertNotNull($organization->fresh()->getFirstMedia('avatar'));
    }

    public function test_member_can_leave_an_organization_with_other_members(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $organization->users()->attach(User::factory()->create());
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->call('leave')
            ->assertNotified()
            ->assertRedirect();

        $this->assertFalse($organization->fresh()->users->contains($user));
    }

    public function test_only_member_cannot_leave_the_organization(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->call('leave');

        $this->assertTrue($organization->fresh()->users->contains($user));
    }

    public function test_member_can_delete_the_organization(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(GeneralSettings::class)
            ->call('delete')
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
    }
}
