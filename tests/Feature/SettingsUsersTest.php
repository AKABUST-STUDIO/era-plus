<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\Members as MembersPage;
use App\Models\Organization;
use App\Models\User;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsUsersTest extends TestCase
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
        $user->joinOrganization($organization);

        return [$user, $organization];
    }

    public function test_page_loads(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(MembersPage::class)
            ->assertOk();
    }

    public function test_table_lists_only_organization_members(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $fellowMember = User::factory()->create();
        $fellowMember->joinOrganization($organization);

        $outsider = User::factory()->create();

        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(MembersPage::class)
            ->assertCanSeeTableRecords([$user, $fellowMember])
            ->assertCanNotSeeTableRecords([$outsider]);
    }

    public function test_invite_requires_an_email(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $organization->users()->updateExistingPivot($user->id, [
            'role_id' => $organization->roleFor(OrganizationRole::Admin)?->id,
        ]);
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(MembersPage::class)
            ->fillForm(['email' => ''], 'inviteForm')
            ->call('invite')
            ->assertHasFormErrors(['email' => 'required'], 'inviteForm');
    }

    public function test_invite_attaches_member(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $organization->users()->updateExistingPivot($user->id, [
            'role_id' => $organization->roleFor(OrganizationRole::Admin)?->id,
        ]);
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(MembersPage::class)
            ->fillForm(['email' => 'newcomer@example.com'], 'inviteForm')
            ->call('invite')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(2, $organization->users()->count());
    }

    public function test_member_cannot_see_invite_section(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnSettingsPanel($user, $organization);

        Livewire::test(MembersPage::class)
            ->assertDontSee(__('settings.users.invite.heading'));
    }
}
