<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Filament\Organization\Settings\Pages\Users as UsersPage;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class UsersPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Maria']);
        $this->organization = Organization::factory()->create();
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
            ->fillForm(['email' => 'newbie@example.com', 'role' => OrganizationRole::Coordinator->value], 'inviteForm')
            ->call('invite');

        $this->assertDatabaseHas('users', ['email' => 'newbie@example.com']);

        $newbie = User::query()->where('email', 'newbie@example.com')->firstOrFail();
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $newbie->id,
            'role_id' => $this->organization->roleFor(OrganizationRole::Coordinator)->id,
        ]);
    }

    public function test_invite_existing_user_attaches_without_creating(): void
    {
        $existing = User::factory()->create(['email' => 'old@example.com']);

        $countBefore = User::query()->count();

        Livewire::test(UsersPage::class)
            ->fillForm(['email' => 'old@example.com', 'role' => OrganizationRole::Member->value], 'inviteForm')
            ->call('invite');

        $this->assertSame($countBefore, User::query()->count());
        $this->assertTrue($this->organization->fresh()->users->contains($existing));
    }

    public function test_change_role_persists(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        Livewire::test(UsersPage::class)
            ->instance()
            ->changeRole($member, OrganizationRole::Leader);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
            'role_id' => $this->organization->roleFor(OrganizationRole::Leader)->id,
        ]);
    }

    public function test_remove_member_detaches(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        Livewire::test(UsersPage::class)
            ->instance()
            ->removeMember($member);

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_cannot_remove_last_admin(): void
    {
        Livewire::test(UsersPage::class)
            ->instance()
            ->removeMember($this->user);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_listing_shows_you_badge_for_current_user(): void
    {
        $other = User::factory()->create(['name' => 'Anne']);
        $other->joinOrganization($this->organization, OrganizationRole::Member);

        Livewire::test(UsersPage::class)
            ->assertSee('Maria')
            ->assertSee('Anne')
            ->assertSee(__('settings.users.table.you'));
    }
}
