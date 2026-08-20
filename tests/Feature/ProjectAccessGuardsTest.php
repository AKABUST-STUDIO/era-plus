<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Project\Resources\ProjectEvents\ProjectEventResource;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
use App\Filament\Project\Resources\ProjectParticipants\ProjectParticipantResource;
use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->projectA = Project::factory()->for($this->organization)->create(['name' => 'Alpha']);
    $this->projectB = Project::factory()->for($this->organization)->create(['name' => 'Beta']);

    $this->outsider = User::factory()->create();

    $this->bareMember = User::factory()->create();
    $this->bareMember->joinOrganization($this->organization, OrganizationRole::Member);

    $this->participant = User::factory()->create();
    $this->participant->joinOrganization($this->organization, OrganizationRole::Member);
    $this->participant->joinProject($this->projectA, ProjectRole::Participant);

    $this->projectAdmin = User::factory()->create();
    $this->projectAdmin->joinOrganization($this->organization, OrganizationRole::Member);
    $this->projectAdmin->joinProject($this->projectA, ProjectRole::Admin);

    URL::defaults(['organization' => $this->organization->slug]);
});

function projectResourceUrl(string $resource, Project $project, string $page = 'index'): string
{
    Filament::setCurrentPanel(Filament::getPanel('project'));

    return $resource::getUrl($page, tenant: $project);
}

test('outsider cannot reach any project url', function (): void {
    $this->actingAs($this->outsider);

    $this->get(projectResourceUrl(ProjectMemberResource::class, $this->projectA))->assertForbidden();
    $this->get(projectResourceUrl(ProjectEventResource::class, $this->projectA))->assertForbidden();
    $this->get(projectResourceUrl(ProjectParticipantResource::class, $this->projectA))->assertForbidden();
    $this->get(projectResourceUrl(TravelExpenseResource::class, $this->projectA))->assertForbidden();
});

test('bare org member cannot reach project deep urls', function (): void {
    $this->actingAs($this->bareMember);

    $this->get(projectResourceUrl(ProjectMemberResource::class, $this->projectA))->assertForbidden();
    $this->get(projectResourceUrl(ProjectEventResource::class, $this->projectA))->assertForbidden();
    $this->get(projectResourceUrl(ProjectParticipantResource::class, $this->projectA))->assertForbidden();
    $this->get(projectResourceUrl(TravelExpenseResource::class, $this->projectA))->assertForbidden();
});

test('participant cannot reach a project they are not on', function (): void {
    $this->actingAs($this->participant);

    foreach ([ProjectMemberResource::class, ProjectEventResource::class] as $resource) {
        $status = $this->get(projectResourceUrl($resource, $this->projectB))->status();
        $this->assertTrue(in_array($status, [403, 404], true), "{$resource} on projectB: expected 403|404, got {$status}");
    }
});

test('participant cannot view participants page', function (): void {
    $this->actingAs($this->participant);

    $this->get(projectResourceUrl(ProjectParticipantResource::class, $this->projectA))->assertForbidden();
});

test('participant cannot view travel expenses page', function (): void {
    $this->actingAs($this->participant);

    $this->get(projectResourceUrl(TravelExpenseResource::class, $this->projectA))->assertForbidden();
});

test('participant can view users and events pages', function (): void {
    $this->actingAs($this->participant);

    $this->get(projectResourceUrl(ProjectMemberResource::class, $this->projectA))->assertSuccessful();
    $this->get(projectResourceUrl(ProjectEventResource::class, $this->projectA))->assertSuccessful();
});

test('bare org member cannot edit a project', function (): void {
    $this->actingAs($this->bareMember);
    Filament::setCurrentPanel(Filament::getPanel('organization'));

    $url = ProjectResource::getUrl('edit', ['record' => $this->projectA], tenant: $this->organization);

    $this->get($url)->assertForbidden();
});

test('bare org member cannot reach create project', function (): void {
    $this->actingAs($this->bareMember);
    Filament::setCurrentPanel(Filament::getPanel('organization'));

    $url = ProjectResource::getUrl('create', tenant: $this->organization);

    $status = $this->get($url)->status();
    $this->assertTrue(in_array($status, [403, 404], true), "expected 403 or 404, got {$status}");
});

test('outsider cannot reach create project', function (): void {
    $this->actingAs($this->outsider);
    Filament::setCurrentPanel(Filament::getPanel('organization'));

    $url = ProjectResource::getUrl('create', tenant: $this->organization);

    $status = $this->get($url)->status();
    $this->assertTrue(in_array($status, [403, 404], true), "expected 403 or 404, got {$status}");
});

test('participant cannot reach create project via url', function (): void {
    $this->actingAs($this->participant);
    Filament::setCurrentPanel(Filament::getPanel('organization'));

    $url = ProjectResource::getUrl('create', tenant: $this->organization);

    $status = $this->get($url)->status();
    $this->assertTrue(in_array($status, [403, 404], true), "expected 403 or 404, got {$status}");
});

test('participant member table hides invite action', function (): void {
    $this->actingAs($this->participant);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->projectA);

    Livewire::test(ListProjectMembers::class)
        ->assertOk()
        ->assertActionHidden('create');
});

test('project admin member table shows invite action', function (): void {
    $this->actingAs($this->projectAdmin);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->projectA);

    Livewire::test(ListProjectMembers::class)
        ->assertOk()
        ->assertActionVisible('create');
});

test('participant cannot load edit project page', function (): void {
    $this->actingAs($this->participant);
    Filament::setCurrentPanel(Filament::getPanel('organization'));

    $url = ProjectResource::getUrl('edit', ['record' => $this->projectA], tenant: $this->organization);

    $this->get($url)->assertForbidden();
});

test('participant cannot edit project', function (): void {
    $this->actingAs($this->participant);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);

    expect(ProjectResource::canEdit($this->projectA))->toBeFalse();
});

test('participant project resource cannot create', function (): void {
    $this->actingAs($this->participant);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);

    expect(ProjectResource::canCreate())->toBeFalse();
});
