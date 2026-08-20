<?php

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Resources\OrganizationUsers\Pages\ListOrganizationUsers as UsersPage;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create(['name' => 'Maria']);
    $this->organization = Organization::factory()->create();
    $this->memberRole = $this->organization->roleFor(OrganizationRole::Member);
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);
});

function usersMembership(Organization $organization, User $user): OrganizationUser
{
    return OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $user->id)
        ->firstOrFail();
}

test('page renders', function (): void {
    Livewire::test(UsersPage::class)->assertSuccessful();
});

test('page and every modal resolve their translation keys', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->assertDontSee('settings.users.')
        ->assertDontSee('forms.common.')
        ->mountAction('create')
        ->assertDontSee('settings.users.')
        ->unmountAction()
        ->mountAction(TestAction::make('changeRole')->table(usersMembership($this->organization, $member)))
        ->assertDontSee('settings.users.')
        ->unmountAction()
        ->mountAction(TestAction::make('delete')->table(usersMembership($this->organization, $member)))
        ->assertDontSee('settings.users.');
});

test('invite creates new user and attaches to org', function (): void {
    Livewire::test(UsersPage::class)
        ->callAction('create', data: [
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
});

test('invite existing user attaches without creating', function (): void {
    $existing = User::factory()->create(['email' => 'old@example.com']);

    $countBefore = User::query()->count();

    Livewire::test(UsersPage::class)
        ->callAction('create', data: ['email' => 'old@example.com', 'role' => (string) $this->memberRole->id])
        ->assertHasNoFormErrors();

    $this->assertSame($countBefore, User::query()->count());
    $this->assertTrue($this->organization->fresh()->users->contains($existing));
});

test('change role persists', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, $this->memberRole);

    $adminRoleId = $this->organization->roleFor(OrganizationRole::Admin)->id;

    Livewire::test(UsersPage::class)
        ->callAction(
            TestAction::make('changeRole')->table(usersMembership($this->organization, $member)),
            data: ['role' => (string) $adminRoleId],
        );

    $this->assertDatabaseHas('organization_users', [
        'organization_id' => $this->organization->id,
        'user_id' => $member->id,
        'role_id' => $adminRoleId,
    ]);
});

test('remove member detaches', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->callAction(TestAction::make('delete')->table(usersMembership($this->organization, $member)));

    $this->assertDatabaseMissing('organization_users', [
        'organization_id' => $this->organization->id,
        'user_id' => $member->id,
    ]);
});

test('cannot remove last admin', function (): void {
    Livewire::test(UsersPage::class)
        ->callAction(TestAction::make('delete')->table(usersMembership($this->organization, $this->user)));

    $this->assertDatabaseHas('organization_users', [
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
    ]);
});

test('pending admin does not count as a second admin', function (): void {
    $pendingAdmin = User::factory()->create(['email_verified_at' => null]);
    $pendingAdmin->joinOrganization($this->organization, OrganizationRole::Admin);

    Livewire::test(UsersPage::class)
        ->callAction(TestAction::make('delete')->table(usersMembership($this->organization, $this->user)));

    $this->assertDatabaseHas('organization_users', [
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test(UsersPage::class)
        ->set('activeTab', UsersPage::TAB_INVITATIONS)
        ->callAction(TestAction::make('delete')->table(usersMembership($this->organization, $pendingAdmin)));

    $this->assertDatabaseMissing('organization_users', [
        'organization_id' => $this->organization->id,
        'user_id' => $pendingAdmin->id,
    ]);
});

test('user listing shows you badge for current user', function (): void {
    $other = User::factory()->create(['name' => 'Anne']);
    $other->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->assertSee('Maria')
        ->assertSee('Anne')
        ->assertSee(__('settings.users.table.you'));
});

test('invite new user sends invitation mail', function (): void {
    Notification::fake();

    Livewire::test(UsersPage::class)
        ->callAction('create', data: ['email' => 'fresh@example.com', 'role' => (string) $this->memberRole->id])
        ->assertHasNoFormErrors();

    Notification::assertSentTo(
        User::query()->where('email', 'fresh@example.com')->firstOrFail(),
        OrganizationInvitationNotification::class,
    );
});

test('invite existing user sends invitation mail', function (): void {
    $existing = User::factory()->create(['email' => 'known@example.com']);
    Notification::fake();

    Livewire::test(UsersPage::class)
        ->callAction('create', data: ['email' => 'known@example.com', 'role' => (string) $this->memberRole->id])
        ->assertHasNoFormErrors();

    Notification::assertSentTo($existing, OrganizationInvitationNotification::class);
});

test('invitation link signs the invitee in', function (): void {
    Notification::fake();

    Livewire::test(UsersPage::class)
        ->callAction('create', data: ['email' => 'invited@example.com', 'role' => (string) $this->memberRole->id])
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
});

test('two factor filter narrows to users with two factor', function (): void {
    $secured = User::factory()->create([
        'name' => 'Sofia',
        'two_factor_confirmed_at' => now(),
    ]);
    $secured->joinOrganization($this->organization, $this->memberRole);

    $insecure = User::factory()->create(['name' => 'Ivan']);
    $insecure->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->filterTable('group', ['two_factor' => '1'])
        ->assertCanSeeTableRecords([usersMembership($this->organization, $secured)])
        ->assertCanNotSeeTableRecords([usersMembership($this->organization, $insecure)]);
});

test('search matches name and email', function (): void {
    $anne = User::factory()->create(['name' => 'Anne', 'email' => 'anne@example.com']);
    $anne->joinOrganization($this->organization, $this->memberRole);

    $zack = User::factory()->create(['name' => 'Zack', 'email' => 'zack@example.com']);
    $zack->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->searchTable('Anne')
        ->assertCanSeeTableRecords([usersMembership($this->organization, $anne)])
        ->assertCanNotSeeTableRecords([usersMembership($this->organization, $zack)])
        ->searchTable('zack@example.com')
        ->assertCanSeeTableRecords([usersMembership($this->organization, $zack)])
        ->assertCanNotSeeTableRecords([usersMembership($this->organization, $anne)]);
});

test('role filter narrows to a single role', function (): void {
    $member = User::factory()->create(['name' => 'Ivan']);
    $member->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->filterTable('group', ['role' => (string) $this->memberRole->id])
        ->assertCanSeeTableRecords([usersMembership($this->organization, $member)])
        ->assertCanNotSeeTableRecords([usersMembership($this->organization, $this->user)]);
});

test('invitations tab lists only unverified users', function (): void {
    $invited = User::factory()->create(['name' => 'Pending', 'email_verified_at' => null]);
    $invited->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->assertCanNotSeeTableRecords([usersMembership($this->organization, $invited)])
        ->set('activeTab', UsersPage::TAB_INVITATIONS)
        ->assertCanSeeTableRecords([usersMembership($this->organization, $invited)])
        ->assertCanNotSeeTableRecords([usersMembership($this->organization, $this->user)]);
});

test('pending rows are badged and faded', function (): void {
    $invited = User::factory()->create(['name' => 'Pending', 'email_verified_at' => null]);
    $invited->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->assertDontSee('opacity-60')
        ->set('activeTab', UsersPage::TAB_INVITATIONS)
        ->assertSee(__('settings.users.table.pending'))
        ->assertSee('opacity-60');
});

test('sole admin is not selectable', function (): void {
    $page = Livewire::test(UsersPage::class)->instance();

    $this->assertFalse($page->getTable()->isRecordSelectable(usersMembership($this->organization, $this->user)));
});

test('name column is sortable', function (): void {
    User::query()->update(['email_verified_at' => now()]);

    $zack = User::factory()->create(['name' => 'Zack', 'email_verified_at' => now()]);
    $zack->joinOrganization($this->organization, $this->memberRole);

    $anne = User::factory()->create(['name' => 'Anne', 'email_verified_at' => now()]);
    $anne->joinOrganization($this->organization, $this->memberRole);

    Livewire::test(UsersPage::class)
        ->sortTable('user.name', 'asc')
        ->assertCanSeeTableRecords(
            [usersMembership($this->organization, $anne), usersMembership($this->organization, $this->user), usersMembership($this->organization, $zack)],
            inOrder: true,
        );
});
