<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Pages\OrganizationUsers as UsersPage;
use App\Mail\OrganizationInvitation;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_page_renders(): void
    {
        Livewire::test(UsersPage::class)->assertSuccessful();
    }

    public function test_invite_creates_new_user_and_attaches_to_org(): void
    {
        Livewire::test(UsersPage::class)
            ->fillForm(['email' => 'newbie@example.com', 'role' => (string) $this->organization->roleFor(OrganizationRole::Admin)->id], 'inviteForm')
            ->call('invite');

        $this->assertDatabaseHas('users', ['email' => 'newbie@example.com']);

        $newbie = User::query()->where('email', 'newbie@example.com')->firstOrFail();
        $this->assertDatabaseHas('organization_user', [
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
            ->fillForm(['email' => 'old@example.com', 'role' => (string) $this->memberRole->id], 'inviteForm')
            ->call('invite');

        $this->assertSame($countBefore, User::query()->count());
        $this->assertTrue($this->organization->fresh()->users->contains($existing));
    }

    public function test_change_role_persists(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->instance()
            ->changeRole($member, $this->organization->roleFor(OrganizationRole::Admin)->id);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
            'role_id' => $this->organization->roleFor(OrganizationRole::Admin)->id,
        ]);
    }

    public function test_remove_member_detaches(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->instance()
            ->removeUser($member);

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_cannot_remove_last_admin(): void
    {
        Livewire::test(UsersPage::class)
            ->instance()
            ->removeUser($this->user);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
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
        Mail::fake();

        Livewire::test(UsersPage::class)
            ->fillForm(['email' => 'fresh@example.com', 'role' => (string) $this->memberRole->id], 'inviteForm')
            ->call('invite');

        Mail::assertQueued(
            OrganizationInvitation::class,
            fn (OrganizationInvitation $mail): bool => $mail->hasTo('fresh@example.com'),
        );
    }

    public function test_invite_existing_user_sends_invitation_mail(): void
    {
        $existing = User::factory()->create(['email' => 'known@example.com']);
        Mail::fake();

        Livewire::test(UsersPage::class)
            ->fillForm(['email' => 'known@example.com', 'role' => (string) $this->memberRole->id], 'inviteForm')
            ->call('invite');

        Mail::assertQueued(
            OrganizationInvitation::class,
            fn (OrganizationInvitation $mail) => $mail->hasTo($existing->email),
        );
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
            ->filterTable('two_factor_confirmed_at', true)
            ->assertCanSeeTableRecords([$secured])
            ->assertCanNotSeeTableRecords([$insecure]);
    }

    public function test_sole_admin_is_not_selectable(): void
    {
        $page = Livewire::test(UsersPage::class)->instance();

        $this->assertFalse($page->getTable()->isRecordSelectable($this->user));
    }

    public function test_name_column_is_sortable(): void
    {
        User::query()->update(['email_verified_at' => now()]);

        $zack = User::factory()->create(['name' => 'Zack', 'email_verified_at' => now()]);
        $zack->joinOrganization($this->organization, $this->memberRole);

        $anne = User::factory()->create(['name' => 'Anne', 'email_verified_at' => now()]);
        $anne->joinOrganization($this->organization, $this->memberRole);

        Livewire::test(UsersPage::class)
            ->sortTable('name', 'asc')
            ->assertCanSeeTableRecords([$anne, $zack], inOrder: true);
    }
}
