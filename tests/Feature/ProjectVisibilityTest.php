<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Filament\Resources\Projects\ProjectResource;
use App\Livewire\ProjectMenu;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->projectA = Project::factory()->for($this->organization)->create(['name' => 'Alpha Project']);
    $this->projectB = Project::factory()->for($this->organization)->create(['name' => 'Beta Project']);

    OrganizationService::remember($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);
});

function actAsInOrganizationPanel(User $user, Organization $organization): void
{
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($organization);
}

function actAsInProjectPanel(User $user, Project $project): void
{
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($project);
    ProjectService::remember($project);
}

test('bare org member has no projects in the switcher', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    actAsInOrganizationPanel($member, $this->organization);

    expect(app(App\Services\ProjectService::class)->projectsFor($member, $this->organization))->toHaveCount(0);
});

test('bare org member gets no project tenants', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    expect($member->getTenants(Filament::getPanel('project')))->toHaveCount(0);
});

test('bare org member cannot access project panel', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    expect($member->canAccessPanel(Filament::getPanel('project')))->toBeFalse();
});

test('bare org member cannot view a project they are not on', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    actAsInOrganizationPanel($member, $this->organization);

    expect(Gate::forUser($member)->allows('view', $this->projectA))->toBeFalse()
        ->and(Gate::forUser($member)->allows('view', $this->projectB))->toBeFalse();
});

test('bare org member cannot create projects', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    actAsInOrganizationPanel($member, $this->organization);

    expect(ProjectResource::canCreate())->toBeFalse();
});

test('participant sees only their project in the switcher', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization, OrganizationRole::Member);
    $participant->joinProject($this->projectA, ProjectRole::Participant);

    $projects = app(App\Services\ProjectService::class)->projectsFor($participant, $this->organization);

    expect($projects)->toHaveCount(1)
        ->and($projects->first()->is($this->projectA))->toBeTrue();
});

test('participant can access only their project as tenant', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization, OrganizationRole::Member);
    $participant->joinProject($this->projectA, ProjectRole::Participant);

    expect($participant->canAccessTenant($this->projectA))->toBeTrue()
        ->and($participant->canAccessTenant($this->projectB))->toBeFalse();
});

test('bare org member cannot access any project as tenant', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    expect($member->canAccessTenant($this->projectA))->toBeFalse()
        ->and($member->canAccessTenant($this->projectB))->toBeFalse();
});

test('participant cannot view other projects in the same org', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization, OrganizationRole::Member);
    $participant->joinProject($this->projectA, ProjectRole::Participant);

    actAsInProjectPanel($participant, $this->projectA);

    expect(Gate::forUser($participant)->allows('view', $this->projectA))->toBeTrue()
        ->and(Gate::forUser($participant)->allows('view', $this->projectB))->toBeFalse();
});

test('participant cannot create projects', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization, OrganizationRole::Member);
    $participant->joinProject($this->projectA, ProjectRole::Participant);

    actAsInProjectPanel($participant, $this->projectA);

    expect(ProjectResource::canCreate())->toBeFalse();
});

test('project menu hides create url for participant', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization, OrganizationRole::Member);
    $participant->joinProject($this->projectA, ProjectRole::Participant);

    actAsInProjectPanel($participant, $this->projectA);

    $view = Livewire::test(ProjectMenu::class)->assertOk();

    expect($view->viewData('createUrl'))->toBeNull();
});

test('project menu hides create url for bare org member', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    actAsInOrganizationPanel($member, $this->organization);

    $view = Livewire::test(ProjectMenu::class)->assertOk();

    expect($view->viewData('createUrl'))->toBeNull();
});

test('project menu exposes create url for org admin', function (): void {
    $freshOrganization = Organization::factory()->create();
    $admin = User::factory()->create();
    $admin->joinOrganization($freshOrganization, OrganizationRole::Admin);

    OrganizationService::remember($freshOrganization);
    URL::defaults(['organization' => $freshOrganization->slug]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($freshOrganization);

    $view = Livewire::test(ProjectMenu::class)->assertOk();

    expect($view->viewData('createUrl'))->toBeString()->not->toBe('');
});

test('participant project switcher does not leak other projects', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization, OrganizationRole::Member);
    $participant->joinProject($this->projectA, ProjectRole::Participant);

    actAsInProjectPanel($participant, $this->projectA);

    Livewire::test(ProjectMenu::class)
        ->assertOk()
        ->assertSee($this->projectA->name)
        ->assertDontSee($this->projectB->name);
});
