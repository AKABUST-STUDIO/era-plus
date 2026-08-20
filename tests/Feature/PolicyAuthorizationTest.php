<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Pages\Activity;
use App\Filament\Organization\Settings\Pages\Billing;
use App\Filament\Organization\Settings\Pages\Invoices;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Filament\Organization\Settings\Resources\Roles\Pages\ListRoles;
use App\Filament\Project\Resources\ProjectParticipants\ProjectParticipantResource;
use App\Filament\Project\Resources\TravelExpenses\Pages\ListTravelExpenses;
use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\CountryLimit;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use App\Models\ProjectUser;
use App\Models\Role;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\Project\CountryLimitPolicy;
use App\Policies\Project\ParticipantPolicy;
use App\Policies\Project\ProjectParticipantPolicy;
use App\Policies\Project\TravelExpensePolicy;
use App\Policies\ProjectUserPolicy;
use App\Policies\RolePolicy;
use App\Services\PermissionRegistry;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

const CRUD_POLICIES = [
    ProjectUserPolicy::class,
    RolePolicy::class,
    ParticipantPolicy::class,
    ProjectParticipantPolicy::class,
    TravelExpensePolicy::class,
];

const SUPPORTED_ABILITIES = [
    'viewAny',
    'view',
    'create',
    'update',
    'updateAny',
    'delete',
    'deleteAny',
];

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->project = Project::factory()->for($this->organization)->create();

    $this->participant = User::factory()->create();
    $this->participant->joinOrganization($this->organization);
    $this->participant->joinProject($this->project, ProjectRole::Participant);

    URL::defaults(['organization' => $this->organization->slug]);
});

function actAsParticipantInProject(User $participant, Project $project): void
{
    test()->actingAs($participant);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($project);
}

function grantToParticipantRole(Project $project, User $participant, string ...$abilities): void
{
    $project->roleFor(ProjectRole::Participant)->givePermissionTo($abilities);
    $participant->unsetRelation('roles');
}

test('crud policies expose only the supported abilities', function (): void {
    foreach (CRUD_POLICIES as $policy) {
        $abilities = array_map(
            fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass($policy))->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        sort($abilities);

        $expected = SUPPORTED_ABILITIES;
        sort($expected);

        $this->assertSame($expected, $abilities, "[{$policy}] does not expose the supported abilities.");
    }
});

test('country limit policy only exposes update', function (): void {
    $abilities = array_map(
        fn (ReflectionMethod $method): string => $method->getName(),
        (new ReflectionClass(CountryLimitPolicy::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    $this->assertSame(['update'], $abilities);
});

test('organization policy only exposes update and delete', function (): void {
    $abilities = array_map(
        fn (ReflectionMethod $method): string => $method->getName(),
        (new ReflectionClass(OrganizationPolicy::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    $this->assertSame(['update', 'delete'], $abilities);
    $this->assertSame(
        ['update_organization', 'delete_organization'],
        PermissionRegistry::grouped()['organization'],
    );
});

test('project permissions are organization wide only', function (): void {
    $this->assertSame(
        ['view_any_project', 'create_project', 'update_any_project', 'delete_any_project'],
        PermissionRegistry::grouped()['project'],
    );
});

test('project view is membership based and not permission gated', function (): void {
    $this->assertArrayNotHasKey(
        'view_project',
        array_flip(PermissionRegistry::grouped()['project'] ?? []),
    );
});

test('organization settings gate follows the organization policy', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);
    $role = $this->organization->roleFor(OrganizationRole::Member);

    $this->actingAs($member);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    Filament::setTenant($this->organization);

    $this->assertTrue(OrganizationSettings::canAccess());
    $this->assertFalse($member->can('update', $this->organization));
    $this->assertFalse($member->can('delete', $this->organization));

    $role->givePermissionTo('update_organization');
    $member->unsetRelation('roles');

    $this->assertTrue($member->fresh()->can('update', $this->organization));
});

test('activity page is gated by its own policy', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);
    $role = $this->organization->roleFor(OrganizationRole::Member);

    $this->actingAs($member);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    Filament::setTenant($this->organization);
    OrganizationService::remember($this->organization);

    $this->assertSame(['view'], array_map(
        fn (ReflectionMethod $method): string => $method->getName(),
        (new ReflectionClass(ActivityLogPolicy::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    ));
    $this->assertSame(['view_activity'], PermissionRegistry::grouped()['activity']);

    $this->assertFalse(Activity::canAccess());

    $role->givePermissionTo('view_activity');

    $this->assertTrue(Activity::canAccess());
});

test('billing and invoices pages are gated by their own policies', function (): void {
    $this->markTestSkipped('Billing and Invoices pages are intentionally hidden.');

    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);
    $role = $this->organization->roleFor(OrganizationRole::Member);

    $this->actingAs($member);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    Filament::setTenant($this->organization);
    OrganizationService::remember($this->organization);

    $this->assertSame(['view_billing', 'update_billing'], PermissionRegistry::grouped()['billing']);
    $this->assertSame(['view_invoice'], PermissionRegistry::grouped()['invoice']);

    $this->assertFalse(Billing::canAccess());
    $this->assertFalse(Invoices::canAccess());
    $this->assertFalse($member->can('update', Billing::class));

    $role->givePermissionTo('view_billing', 'view_invoice');

    $this->assertTrue(Billing::canAccess());
    $this->assertTrue(Invoices::canAccess());
    $this->assertFalse($member->fresh()->can('update', Billing::class));

    $role->givePermissionTo('update_billing');

    $this->assertTrue($member->fresh()->can('update', Billing::class));
});

test('update any follows the granted project permission', function (): void {
    actAsParticipantInProject($this->participant, $this->project);

    $this->assertFalse(Gate::allows('updateAny', TravelExpense::class));
    $this->assertFalse(Gate::allows('updateAny', ProjectParticipant::class));
    $this->assertFalse(Gate::allows('updateAny', Participant::class));
    $this->assertFalse(Gate::allows('updateAny', ProjectUser::class));
    $this->assertFalse(Gate::allows('update', CountryLimit::class));

    grantToParticipantRole(
        $this->project,
        $this->participant,
        'update_any_travel_expense',
        'update_any_participant',
        'update_any_project_user',
        'country_limits_travel_expense',
    );

    $this->assertTrue(Gate::allows('updateAny', TravelExpense::class));
    $this->assertTrue(Gate::allows('updateAny', ProjectParticipant::class));
    $this->assertTrue(Gate::allows('updateAny', Participant::class));
    $this->assertTrue(Gate::allows('updateAny', ProjectUser::class));
    $this->assertTrue(Gate::allows('update', CountryLimit::class));
});

test('participant resource delegates to the policy', function (): void {
    actAsParticipantInProject($this->participant, $this->project);

    $this->assertFalse(ProjectParticipantResource::canViewAny());
    $this->assertFalse(ProjectParticipantResource::canCreate());

    grantToParticipantRole($this->project, $this->participant, 'view_any_participant', 'create_participant');

    $this->assertTrue(ProjectParticipantResource::canViewAny());
    $this->assertTrue(ProjectParticipantResource::canCreate());
});

test('travel expense resource delegates to the policy', function (): void {
    actAsParticipantInProject($this->participant, $this->project);

    $this->assertFalse(TravelExpenseResource::canViewAny());

    grantToParticipantRole($this->project, $this->participant, 'view_any_travel_expense', 'create_travel_expense');

    $this->assertTrue(TravelExpenseResource::canViewAny());
    $this->assertTrue(TravelExpenseResource::canCreate());
});

test('country limits action is authorized by the country limit policy', function (): void {
    actAsParticipantInProject($this->participant, $this->project);
    grantToParticipantRole($this->project, $this->participant, 'view_any_travel_expense');

    Livewire::test(ListTravelExpenses::class)
        ->assertActionHidden('countryLimits');

    grantToParticipantRole($this->project, $this->participant, 'country_limits_travel_expense');

    Livewire::test(ListTravelExpenses::class)
        ->assertActionVisible('countryLimits');
});

test('import and export are authorized by their own permissions', function (): void {
    actAsParticipantInProject($this->participant, $this->project);
    grantToParticipantRole($this->project, $this->participant, 'view_any_travel_expense', 'create_travel_expense');

    Livewire::test(ListTravelExpenses::class)
        ->assertActionHidden('export')
        ->assertActionHidden('import');

    grantToParticipantRole($this->project, $this->participant, 'import_travel_expense', 'export_travel_expense');

    Livewire::test(ListTravelExpenses::class)
        ->assertActionVisible('export')
        ->assertActionVisible('import');
});

test('role create action is authorized by the role policy', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);
    $this->organization->roleFor(OrganizationRole::Member)->givePermissionTo('view_any_role');

    $this->actingAs($member);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    Filament::setTenant($this->organization);

    Livewire::test(ListRoles::class)
        ->assertActionHidden('create');

    $this->organization->roleFor(OrganizationRole::Member)->givePermissionTo('create_role');

    Livewire::test(ListRoles::class)
        ->assertActionVisible('create');
});

test('travel expense resource declaring the interface appears in the registry', function (): void {
    $grouped = PermissionRegistry::grouped(PermissionRegistry::SCOPE_PROJECT);

    $this->assertArrayHasKey('travel_expense', $grouped);
    $this->assertSame(
        [
            'view_any_travel_expense',
            'create_travel_expense',
            'update_travel_expense',
            'update_any_travel_expense',
            'delete_travel_expense',
            'delete_any_travel_expense',
            'import_travel_expense',
            'export_travel_expense',
            'country_limits_travel_expense',
        ],
        $grouped['travel_expense'],
    );
});

test('organization user resource declaring the interface appears in the registry', function (): void {
    $grouped = PermissionRegistry::grouped(PermissionRegistry::SCOPE_ORGANIZATION);

    $this->assertArrayHasKey('organization_user', $grouped);
    $this->assertSame(
        [
            'view_any_organization_user',
            'view_organization_user',
            'create_organization_user',
            'update_organization_user',
            'update_any_organization_user',
            'delete_organization_user',
            'delete_any_organization_user',
        ],
        $grouped['organization_user'],
    );
});

test('dropped country limit group is absent from the registry', function (): void {
    $projectGrouped = PermissionRegistry::grouped(PermissionRegistry::SCOPE_PROJECT);
    $orgGrouped = PermissionRegistry::grouped(PermissionRegistry::SCOPE_ORGANIZATION);

    $this->assertArrayNotHasKey('country_limit', $projectGrouped);
    $this->assertArrayNotHasKey('country_limit', $orgGrouped);
});

test('locked roles cannot be updated or deleted', function (): void {
    $admin = User::factory()->create();
    $admin->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    Filament::setTenant($this->organization);

    $locked = $this->organization->roleFor(OrganizationRole::Admin);
    $unlocked = $this->organization->roleFor(OrganizationRole::Member);

    $this->assertFalse(Gate::allows('update', $locked));
    $this->assertFalse(Gate::allows('delete', $locked));
    $this->assertTrue(Gate::allows('update', $unlocked));
    $this->assertTrue(Gate::allows('delete', $unlocked));
    $this->assertTrue(Gate::allows('create', Role::class));
});
