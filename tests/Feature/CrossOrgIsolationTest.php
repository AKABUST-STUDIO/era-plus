<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Filament\Project\Settings\Resources\ProjectMembers\ProjectMemberResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;
use App\Services\ProjectService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->orgA = Organization::factory()->create(['name' => 'Org A']);
    $this->orgB = Organization::factory()->create(['name' => 'Org B']);

    $this->projectA = Project::factory()->for($this->orgA)->create(['name' => 'Project A']);
    $this->projectB = Project::factory()->for($this->orgB)->create(['name' => 'Project B']);
});

test('admin in org a does not get admin powers in org b', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->orgA, OrganizationRole::Admin);
    $user->joinOrganization($this->orgB, OrganizationRole::Member);
    $user->joinProject($this->projectB, ProjectRole::Participant);

    $access = app(ProjectAccess::class);

    $this->assertTrue($access->can($user, 'create_project_user', $this->projectA), 'admin-in-A must have admin powers in projectA');

    $this->assertFalse($access->can($user, 'create_project_user', $this->projectB), 'admin-in-A must NOT have admin powers in projectB');
    $this->assertFalse($access->can($user, 'view_any_participant', $this->projectB));
});

test('participant in org a cannot view projects in org b', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->orgA, OrganizationRole::Member);
    $user->joinProject($this->projectA, ProjectRole::Participant);
    $user->joinOrganization($this->orgB, OrganizationRole::Member);

    $projectsInB = app(ProjectService::class)->projectsFor($user, $this->orgB);
    $this->assertCount(0, $projectsInB);

    $projectsInA = app(ProjectService::class)->projectsFor($user, $this->orgA);
    $this->assertCount(1, $projectsInA);
    $this->assertTrue($projectsInA->first()->is($this->projectA));
});

test('tenant switcher does not leak projects across orgs', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->orgA, OrganizationRole::Member);
    $user->joinOrganization($this->orgB, OrganizationRole::Member);
    $user->joinProject($this->projectA, ProjectRole::Participant);
    $user->joinProject($this->projectB, ProjectRole::Participant);

    $this->assertTrue($user->canAccessTenant($this->projectA));
    $this->assertTrue($user->canAccessTenant($this->projectB));

    $inA = app(ProjectService::class)->projectsFor($user, $this->orgA)->pluck('id')->all();
    $inB = app(ProjectService::class)->projectsFor($user, $this->orgB)->pluck('id')->all();

    $this->assertSame([$this->projectA->id], $inA);
    $this->assertSame([$this->projectB->id], $inB);
});

test('url tampering project in wrong org is rejected', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->orgA, OrganizationRole::Admin);
    $user->joinProject($this->projectA, ProjectRole::Admin);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('project'));

    $url = url("/{$this->orgA->slug}/{$this->projectB->slug}/users");

    $status = $this->get($url)->status();
    $this->assertTrue(in_array($status, [403, 404], true), "cross-org project URL: expected 403|404, got {$status}");
});

test('org admin cannot edit a project in a different org', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->orgA, OrganizationRole::Admin);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->orgA);
    OrganizationService::remember($this->orgA);
    URL::defaults(['organization' => $this->orgA->slug]);

    $this->assertFalse(ProjectResource::canEdit($this->projectB));
    $this->assertFalse(ProjectResource::canDelete($this->projectB));
});

test('participant cannot use project resource in other org', function (): void {
    $user = User::factory()->create();
    $user->joinOrganization($this->orgA, OrganizationRole::Member);
    $user->joinProject($this->projectA, ProjectRole::Participant);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->projectA);

    $this->assertTrue(ProjectMemberResource::canViewAny());

    Filament::setTenant($this->projectB);
    $this->assertFalse(ProjectMemberResource::canViewAny());
});
