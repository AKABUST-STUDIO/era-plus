<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const CRUD_POLICIES = [
        ProjectUserPolicy::class,
        RolePolicy::class,
        ParticipantPolicy::class,
        ProjectParticipantPolicy::class,
        TravelExpensePolicy::class,
    ];

    private const SUPPORTED_ABILITIES = [
        'viewAny',
        'view',
        'create',
        'update',
        'updateAny',
        'delete',
        'deleteAny',
    ];

    private Organization $organization;

    private Project $project;

    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->project = Project::factory()->for($this->organization)->create();

        $this->participant = User::factory()->create();
        $this->participant->joinOrganization($this->organization);
        $this->participant->joinProject($this->project, ProjectRole::Participant);

        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_crud_policies_expose_only_the_supported_abilities(): void
    {
        foreach (self::CRUD_POLICIES as $policy) {
            $abilities = array_map(
                fn (ReflectionMethod $method): string => $method->getName(),
                (new ReflectionClass($policy))->getMethods(ReflectionMethod::IS_PUBLIC),
            );

            sort($abilities);

            $expected = self::SUPPORTED_ABILITIES;
            sort($expected);

            $this->assertSame($expected, $abilities, "[{$policy}] does not expose the supported abilities.");
        }
    }

    public function test_country_limit_policy_only_exposes_update(): void
    {
        $abilities = array_map(
            fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(CountryLimitPolicy::class))->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        $this->assertSame(['update'], $abilities);
    }

    public function test_organization_policy_only_exposes_update_and_delete(): void
    {
        $abilities = array_map(
            fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(OrganizationPolicy::class))->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        $this->assertSame(['update', 'delete'], $abilities);
        $this->assertSame(
            ['update_organization', 'delete_organization'],
            PermissionRegistry::grouped()['organization'],
        );
    }

    public function test_project_permissions_are_organization_wide_only(): void
    {
        $this->assertSame(
            ['view_any_project', 'create_project', 'update_any_project', 'delete_any_project'],
            PermissionRegistry::grouped()['project'],
        );
    }

    public function test_project_view_is_membership_based_and_not_permission_gated(): void
    {
        $this->assertArrayNotHasKey(
            'view_project',
            array_flip(PermissionRegistry::grouped()['project'] ?? []),
        );
    }

    public function test_organization_settings_gate_follows_the_organization_policy(): void
    {
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
    }

    public function test_activity_page_is_gated_by_its_own_policy(): void
    {
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
    }

    public function test_billing_and_invoices_pages_are_gated_by_their_own_policies(): void
    {
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
    }

    public function test_update_any_follows_the_granted_project_permission(): void
    {
        $this->actAsParticipantInProject();

        $this->assertFalse(Gate::allows('updateAny', TravelExpense::class));
        $this->assertFalse(Gate::allows('updateAny', ProjectParticipant::class));
        $this->assertFalse(Gate::allows('updateAny', Participant::class));
        $this->assertFalse(Gate::allows('updateAny', ProjectUser::class));
        $this->assertFalse(Gate::allows('update', CountryLimit::class));

        $this->grantToParticipantRole(
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
    }

    public function test_participant_resource_delegates_to_the_policy(): void
    {
        $this->actAsParticipantInProject();

        $this->assertFalse(ProjectParticipantResource::canViewAny());
        $this->assertFalse(ProjectParticipantResource::canCreate());

        $this->grantToParticipantRole('view_any_participant', 'create_participant');

        $this->assertTrue(ProjectParticipantResource::canViewAny());
        $this->assertTrue(ProjectParticipantResource::canCreate());
    }

    public function test_travel_expense_resource_delegates_to_the_policy(): void
    {
        $this->actAsParticipantInProject();

        $this->assertFalse(TravelExpenseResource::canViewAny());

        $this->grantToParticipantRole('view_any_travel_expense', 'create_travel_expense');

        $this->assertTrue(TravelExpenseResource::canViewAny());
        $this->assertTrue(TravelExpenseResource::canCreate());
    }

    public function test_country_limits_action_is_authorized_by_the_country_limit_policy(): void
    {
        $this->actAsParticipantInProject();
        $this->grantToParticipantRole('view_any_travel_expense');

        Livewire::test(ListTravelExpenses::class)
            ->assertActionHidden('countryLimits');

        $this->grantToParticipantRole('country_limits_travel_expense');

        Livewire::test(ListTravelExpenses::class)
            ->assertActionVisible('countryLimits');
    }

    public function test_import_and_export_are_authorized_by_their_own_permissions(): void
    {
        $this->actAsParticipantInProject();
        $this->grantToParticipantRole('view_any_travel_expense', 'create_travel_expense');

        Livewire::test(ListTravelExpenses::class)
            ->assertActionHidden('export')
            ->assertActionHidden('import');

        $this->grantToParticipantRole('import_travel_expense', 'export_travel_expense');

        Livewire::test(ListTravelExpenses::class)
            ->assertActionVisible('export')
            ->assertActionVisible('import');
    }

    public function test_role_create_action_is_authorized_by_the_role_policy(): void
    {
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
    }

    public function test_travel_expense_resource_declaring_the_interface_appears_in_the_registry(): void
    {
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
    }

    public function test_organization_user_resource_declaring_the_interface_appears_in_the_registry(): void
    {
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
    }

    public function test_dropped_country_limit_group_is_absent_from_the_registry(): void
    {
        $projectGrouped = PermissionRegistry::grouped(PermissionRegistry::SCOPE_PROJECT);
        $orgGrouped = PermissionRegistry::grouped(PermissionRegistry::SCOPE_ORGANIZATION);

        $this->assertArrayNotHasKey('country_limit', $projectGrouped);
        $this->assertArrayNotHasKey('country_limit', $orgGrouped);
    }

    public function test_locked_roles_cannot_be_updated_or_deleted(): void
    {
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
    }

    private function actAsParticipantInProject(): void
    {
        $this->actingAs($this->participant);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
    }

    private function grantToParticipantRole(string ...$abilities): void
    {
        $this->project->roleFor(ProjectRole::Participant)->givePermissionTo($abilities);
        $this->participant->unsetRelation('roles');
    }
}
