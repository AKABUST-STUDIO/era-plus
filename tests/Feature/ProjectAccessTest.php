<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->project = Project::factory()->for($this->organization)->create();
    $this->access = app(ProjectAccess::class);
});

test('org admin bypasses all project permissions', function (): void {
    $admin = User::factory()->create();
    $admin->joinOrganization($this->organization, OrganizationRole::Admin);

    expect($this->access->can($admin, 'view_any_participant', $this->project))->toBeTrue()
        ->and($this->access->can($admin, 'create_project_user', $this->project))->toBeTrue()
        ->and($this->access->can($admin, 'country_limits_travel_expense', $this->project))->toBeTrue();
});

test('project admin bypasses all project permissions', function (): void {
    $projectAdmin = User::factory()->create();
    $projectAdmin->joinOrganization($this->organization);
    $projectAdmin->joinProject($this->project, ProjectRole::Admin);

    expect($this->access->can($projectAdmin, 'view_any_participant', $this->project))->toBeTrue()
        ->and($this->access->can($projectAdmin, 'create_project_user', $this->project))->toBeTrue()
        ->and($this->access->can($projectAdmin, 'country_limits_travel_expense', $this->project))->toBeTrue();
});

test('participant granted a single permission does not get others', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization);
    $participant->joinProject($this->project, ProjectRole::Participant);

    $this->project->roleFor(ProjectRole::Participant)->givePermissionTo('create_participant');

    expect($this->access->can($participant, 'create_participant', $this->project))->toBeTrue()
        ->and($this->access->can($participant, 'create_project_user', $this->project))->toBeFalse()
        ->and($this->access->can($participant, 'country_limits_travel_expense', $this->project))->toBeFalse();
});

test('default participant gets only read permissions', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization);
    $participant->joinProject($this->project, ProjectRole::Participant);

    expect($this->access->can($participant, 'view_any_project_user', $this->project))->toBeTrue()
        ->and($this->access->can($participant, 'view_any_project_event', $this->project))->toBeTrue()
        ->and($this->access->can($participant, 'create_project_user', $this->project))->toBeFalse()
        ->and($this->access->can($participant, 'view_any_participant', $this->project))->toBeFalse()
        ->and($this->access->can($participant, 'create_participant', $this->project))->toBeFalse();
});

test('non member has no permissions', function (): void {
    $stranger = User::factory()->create();

    expect($this->access->can($stranger, 'view_any_participant', $this->project))->toBeFalse()
        ->and($this->access->can($stranger, 'view_any_project_user', $this->project))->toBeFalse();
});

test('gates follow the granted permission', function (): void {
    $viewer = User::factory()->create();
    $viewer->joinOrganization($this->organization);
    $viewer->joinProject($this->project, ProjectRole::Participant);

    $this->project->roleFor(ProjectRole::Participant)->givePermissionTo(['view_any_participant', 'create_participant']);

    $this->actingAs($viewer);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->project);

    expect(Gate::allows('viewAny', Participant::class))->toBeTrue()
        ->and(Gate::allows('create', Participant::class))->toBeTrue()
        ->and(ProjectMemberResource::canViewAny())->toBeTrue()
        ->and(ProjectMemberResource::canCreate())->toBeFalse();
});
