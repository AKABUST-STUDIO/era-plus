<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Resources\OrganizationUsers\Pages\ListOrganizationUsers as UsersPage;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class UsersPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Role $memberRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Maria']);
        $this->organization = Organization::factory()->create();
        $this->memberRole = $this->organization->roleFor(OrganizationRole::Member);
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    private function membership(User $user): OrganizationUser
    {
        return OrganizationUser::query()
            ->where('organization_id', $this->organization->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function test_page_renders(): void
    {
        Livewire::test(UsersPage::class)->assertSuccessful();
    }

    public function test_page_and_every_modal_resolve_their_translation_keys(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->assertDontSee('settings.users.')
            ->assertDontSee('forms.common.')
            ->mountAction('invite')
            ->assertDontSee('settings.users.')
            ->unmountAction()
            ->mountAction(TestAction::make('changeRole')->table($this->membership($member)))
            ->assertDontSee('settings.users.')
            ->unmountAction()
            ->mountAction(TestAction::make('delete')->table($this->membership($member)))
            ->assertDontSee('settings.users.');
    }

    public function test_invite_creates_new_user_and_attaches_to_org(): void
    {
        Livewire::test(UsersPage::class)
            ->callAction('invite', data: [
                'email' => 'newbie@example.com',
                'role' => (string) $this->organization->roleFor(OrganizationRole::Admin)->id,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['email' => 'newbie@example.com']);

        $newbie = User::query()->where('email', 'newbie@example.com')->firstOrFail();
        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $this->organization->id,
            'user_id' => $newbie->id,
            'role_id' => $this->organization->roleFor(OrganizationRole::Admin)->id,
        ]);
    }

    public function test_invite_existing_user_attaches_without_creating(): void
    {
        $existing = User::factory()->create(['email' => 'old@example.com']);

        $countBefore = User::query()->count();

        Livewire::test(UsersPage::class)
            ->callAction('invite', data: ['email' => 'old@example.com', 'role' => (string) $this->memberRole->id])
            ->assertHasNoFormErrors();

        $this->assertSame($countBefore, User::query()->count());
        $this->assertTrue($this->organization->fresh()->users->contains($existing));
    }

    public function test_change_role_persists(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, $this->memberRole);

        $adminRoleId = $this->organization->roleFor(OrganizationRole::Admin)->id;

        Livewire::test(UsersPage::class)
            ->callAction(
                TestAction::make('changeRole')->table($this->membership($member)),
                data: ['role' => (string) $adminRoleId],
            );

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
            'role_id' => $adminRoleId,
        ]);
    }

    public function test_remove_member_detaches(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->callAction(TestAction::make('delete')->table($this->membership($member)));

        $this->assertDatabaseMissing('organization_users', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_cannot_remove_last_admin(): void
    {
        Livewire::test(UsersPage::class)
            ->callAction(TestAction::make('delete')->table($this->membership($this->user)));

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_pending_admin_does_not_count_as_a_second_admin(): void
    {
        $pendingAdmin = User::factory()->create(['email_verified_at' => null]);
        $pendingAdmin->joinOrganization($this->organization, OrganizationRole::Admin);

        Livewire::test(UsersPage::class)
            ->callAction(TestAction::make('delete')->table($this->membership($this->user)));

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);

        Livewire::test(UsersPage::class)
            ->set('activeTab', UsersPage::TAB_INVITATIONS)
            ->callAction(TestAction::make('delete')->table($this->membership($pendingAdmin)));

        $this->assertDatabaseMissing('organization_users', [
            'organization_id' => $this->organization->id,
            'user_id' => $pendingAdmin->id,
        ]);
    }

    public function test_user_listing_shows_you_badge_for_current_user(): void
    {
        $other = User::factory()->create(['name' => 'Anne']);
        $other->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->assertSee('Maria')
            ->assertSee('Anne')
            ->assertSee(__('settings.users.table.you'));
    }

    public function test_invite_new_user_sends_invitation_mail(): void
    {
        Notification::fake();

        Livewire::test(UsersPage::class)
            ->callAction('invite', data: ['email' => 'fresh@example.com', 'role' => (string) $this->memberRole->id])
            ->assertHasNoFormErrors();

        Notification::assertSentTo(
            User::query()->where('email', 'fresh@example.com')->firstOrFail(),
            OrganizationInvitationNotification::class,
        );
    }

    public function test_invite_existing_user_sends_invitation_mail(): void
    {
        $existing = User::factory()->create(['email' => 'known@example.com']);
        Notification::fake();

        Livewire::test(UsersPage::class)
            ->callAction('invite', data: ['email' => 'known@example.com', 'role' => (string) $this->memberRole->id])
            ->assertHasNoFormErrors();

        Notification::assertSentTo($existing, OrganizationInvitationNotification::class);
    }

    public function test_invitation_link_signs_the_invitee_in(): void
    {
        Notification::fake();

        Livewire::test(UsersPage::class)
            ->callAction('invite', data: ['email' => 'invited@example.com', 'role' => (string) $this->memberRole->id])
            ->assertHasNoFormErrors();

        $invited = User::query()->where('email', 'invited@example.com')->firstOrFail();
        $signInUrl = null;

        Notification::assertSentTo($invited, OrganizationInvitationNotification::class, function (OrganizationInvitationNotification $notification) use (&$signInUrl, $invited): bool {
            $signInUrl = $notification->toMail($invited)->viewData['magicLinkUrl'];

            return true;
        });

        $this->assertStringContainsString('/auth/magic-link', $signInUrl);
        $this->assertStringContainsString('signature=', $signInUrl);

        auth()->logout();

        $this->get($signInUrl)->assertRedirect();

        $invitee = User::query()->where('email', 'invited@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($invitee);
        $this->assertNotNull($invitee->fresh()->email_verified_at);
    }

    public function test_two_factor_filter_narrows_to_users_with_two_factor(): void
    {
        $secured = User::factory()->create([
            'name' => 'Sofia',
            'two_factor_confirmed_at' => now(),
        ]);
        $secured->joinOrganization($this->organization, $this->memberRole);

        $insecure = User::factory()->create(['name' => 'Ivan']);
        $insecure->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->filterTable('group', ['two_factor' => '1'])
            ->assertCanSeeTableRecords([$this->membership($secured)])
            ->assertCanNotSeeTableRecords([$this->membership($insecure)]);
    }

    public function test_search_matches_name_and_email(): void
    {
        $anne = User::factory()->create(['name' => 'Anne', 'email' => 'anne@example.com']);
        $anne->joinOrganization($this->organization, $this->memberRole);

        $zack = User::factory()->create(['name' => 'Zack', 'email' => 'zack@example.com']);
        $zack->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->searchTable('Anne')
            ->assertCanSeeTableRecords([$this->membership($anne)])
            ->assertCanNotSeeTableRecords([$this->membership($zack)])
            ->searchTable('zack@example.com')
            ->assertCanSeeTableRecords([$this->membership($zack)])
            ->assertCanNotSeeTableRecords([$this->membership($anne)]);
    }

    public function test_role_filter_narrows_to_a_single_role(): void
    {
        $member = User::factory()->create(['name' => 'Ivan']);
        $member->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->filterTable('group', ['role' => (string) $this->memberRole->id])
            ->assertCanSeeTableRecords([$this->membership($member)])
            ->assertCanNotSeeTableRecords([$this->membership($this->user)]);
    }

    public function test_invitations_tab_lists_only_unverified_users(): void
    {
        $invited = User::factory()->create(['name' => 'Pending', 'email_verified_at' => null]);
        $invited->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->assertCanNotSeeTableRecords([$this->membership($invited)])
            ->set('activeTab', UsersPage::TAB_INVITATIONS)
            ->assertCanSeeTableRecords([$this->membership($invited)])
            ->assertCanNotSeeTableRecords([$this->membership($this->user)]);
    }

    public function test_pending_rows_are_badged_and_faded(): void
    {
        $invited = User::factory()->create(['name' => 'Pending', 'email_verified_at' => null]);
        $invited->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->assertDontSee('opacity-60')
            ->set('activeTab', UsersPage::TAB_INVITATIONS)
            ->assertSee(__('settings.users.table.pending'))
            ->assertSee('opacity-60');
    }

    public function test_sole_admin_is_not_selectable(): void
    {
        $page = Livewire::test(UsersPage::class)->instance();

        $this->assertFalse($page->getTable()->isRecordSelectable($this->membership($this->user)));
    }

    public function test_name_column_is_sortable(): void
    {
        User::query()->update(['email_verified_at' => now()]);

        $zack = User::factory()->create(['name' => 'Zack', 'email_verified_at' => now()]);
        $zack->joinOrganization($this->organization, $this->memberRole);

        $anne = User::factory()->create(['name' => 'Anne', 'email_verified_at' => now()]);
        $anne->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->sortTable('user.name', 'asc')
            ->assertCanSeeTableRecords(
                [$this->membership($anne), $this->membership($this->user), $this->membership($zack)],
                inOrder: true,
            );
    }
}
