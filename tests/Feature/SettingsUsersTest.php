<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Resources\OrganizationUsers\Pages\ListOrganizationUsers as UsersPage;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsUsersTest extends TestCase
{
    use RefreshDatabase;

    private function actingOnOrganizationPanel(User $user, Organization $organization): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);
        URL::defaults(['organization' => $organization->slug]);
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

    private function membership(User $user, Organization $organization): OrganizationUser
    {
        return OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function test_page_loads(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnOrganizationPanel($user, $organization);

        Livewire::test(UsersPage::class)
            ->assertOk();
    }

    public function test_table_lists_only_organization_members(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $fellowMember = User::factory()->create();
        $fellowMember->joinOrganization($organization);

        [$outsider, $otherOrganization] = $this->memberOfOrganization();

        $this->actingOnOrganizationPanel($user, $organization);

        Livewire::test(UsersPage::class)
            ->assertCanSeeTableRecords([
                $this->membership($user, $organization),
                $this->membership($fellowMember, $organization),
            ])
            ->assertCanNotSeeTableRecords([$this->membership($outsider, $otherOrganization)]);
    }

    public function test_invite_requires_an_email(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $organization->users()->updateExistingPivot($user->id, [
            'role_id' => $organization->roleFor(OrganizationRole::Admin)?->id,
        ]);
        $this->actingOnOrganizationPanel($user, $organization);

        Livewire::test(UsersPage::class)
            ->callAction('create', data: ['email' => ''])
            ->assertHasFormErrors(['email' => 'required']);
    }

    public function test_invite_attaches_member(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $organization->users()->updateExistingPivot($user->id, [
            'role_id' => $organization->roleFor(OrganizationRole::Admin)?->id,
        ]);
        $this->actingOnOrganizationPanel($user, $organization);

        Livewire::test(UsersPage::class)
            ->callAction('create', data: ['email' => 'newcomer@example.com'])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(2, $organization->users()->count());
    }

    public function test_member_cannot_see_invite_action(): void
    {
        [$user, $organization] = $this->memberOfOrganization();
        $this->actingOnOrganizationPanel($user, $organization);

        Livewire::test(UsersPage::class)
            ->assertActionHidden('create');
    }
}
