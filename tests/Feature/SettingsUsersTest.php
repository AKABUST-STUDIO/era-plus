<?php

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Resources\OrganizationUsers\Pages\ListOrganizationUsers as UsersPage;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function settingsUsersActingOnOrganizationPanel($test, User $user, Organization $organization): void
{
    $test->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($organization);
    URL::defaults(['organization' => $organization->slug]);
    OrganizationService::remember($organization);
}

function settingsUsersMemberOfOrganization(): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);

    return [$user, $organization];
}

function settingsUsersMembership(User $user, Organization $organization): OrganizationUser
{
    return OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $user->id)
        ->firstOrFail();
}

test('page loads', function (): void {
    [$user, $organization] = settingsUsersMemberOfOrganization();
    settingsUsersActingOnOrganizationPanel($this, $user, $organization);

    Livewire::test(UsersPage::class)
        ->assertOk();
});

test('table lists only organization members', function (): void {
    [$user, $organization] = settingsUsersMemberOfOrganization();
    $fellowMember = User::factory()->create();
    $fellowMember->joinOrganization($organization);

    [$outsider, $otherOrganization] = settingsUsersMemberOfOrganization();

    settingsUsersActingOnOrganizationPanel($this, $user, $organization);

    Livewire::test(UsersPage::class)
        ->assertCanSeeTableRecords([
            settingsUsersMembership($user, $organization),
            settingsUsersMembership($fellowMember, $organization),
        ])
        ->assertCanNotSeeTableRecords([settingsUsersMembership($outsider, $otherOrganization)]);
});

test('invite requires an email', function (): void {
    [$user, $organization] = settingsUsersMemberOfOrganization();
    $organization->users()->updateExistingPivot($user->id, [
        'role_id' => $organization->roleFor(OrganizationRole::Admin)?->id,
    ]);
    settingsUsersActingOnOrganizationPanel($this, $user, $organization);

    Livewire::test(UsersPage::class)
        ->callAction('create', data: ['email' => ''])
        ->assertHasFormErrors(['email' => 'required']);
});

test('invite attaches member', function (): void {
    [$user, $organization] = settingsUsersMemberOfOrganization();
    $organization->users()->updateExistingPivot($user->id, [
        'role_id' => $organization->roleFor(OrganizationRole::Admin)?->id,
    ]);
    settingsUsersActingOnOrganizationPanel($this, $user, $organization);

    Livewire::test(UsersPage::class)
        ->callAction('create', data: ['email' => 'newcomer@example.com'])
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertSame(2, $organization->users()->count());
});

test('member cannot see invite action', function (): void {
    [$user, $organization] = settingsUsersMemberOfOrganization();
    settingsUsersActingOnOrganizationPanel($this, $user, $organization);

    Livewire::test(UsersPage::class)
        ->assertActionHidden('create');
});
