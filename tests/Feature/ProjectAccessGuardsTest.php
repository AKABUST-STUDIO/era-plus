<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectAccessGuardsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Project $projectA;

    private Project $projectB;

    private User $outsider;

    private User $bareMember;

    private User $participant;

    private User $projectAdmin;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    private function projectUrl(string $resource, Project $project, string $page = 'index'): string
    {
        Filament::setCurrentPanel(Filament::getPanel('project'));

        return $resource::getUrl($page, tenant: $project);
    }

    public function test_outsider_cannot_reach_any_project_url(): void
    {
        $this->actingAs($this->outsider);

        $this->get($this->projectUrl(ProjectMemberResource::class, $this->projectA))
            ->assertForbidden();
        $this->get($this->projectUrl(ProjectEventResource::class, $this->projectA))
            ->assertForbidden();
        $this->get($this->projectUrl(ProjectParticipantResource::class, $this->projectA))
            ->assertForbidden();
        $this->get($this->projectUrl(TravelExpenseResource::class, $this->projectA))
            ->assertForbidden();
    }

    public function test_bare_org_member_cannot_reach_project_deep_urls(): void
    {
        $this->actingAs($this->bareMember);

        $this->get($this->projectUrl(ProjectMemberResource::class, $this->projectA))
            ->assertForbidden();
        $this->get($this->projectUrl(ProjectEventResource::class, $this->projectA))
            ->assertForbidden();
        $this->get($this->projectUrl(ProjectParticipantResource::class, $this->projectA))
            ->assertForbidden();
        $this->get($this->projectUrl(TravelExpenseResource::class, $this->projectA))
            ->assertForbidden();
    }

    public function test_participant_cannot_reach_a_project_they_are_not_on(): void
    {
        $this->actingAs($this->participant);

        foreach ([ProjectMemberResource::class, ProjectEventResource::class] as $resource) {
            $status = $this->get($this->projectUrl($resource, $this->projectB))->status();
            $this->assertTrue(in_array($status, [403, 404], true), "{$resource} on projectB: expected 403|404, got {$status}");
        }
    }

    public function test_participant_cannot_view_participants_page(): void
    {
        $this->actingAs($this->participant);

        $this->get($this->projectUrl(ProjectParticipantResource::class, $this->projectA))
            ->assertForbidden();
    }

    public function test_participant_cannot_view_travel_expenses_page(): void
    {
        $this->actingAs($this->participant);

        $this->get($this->projectUrl(TravelExpenseResource::class, $this->projectA))
            ->assertForbidden();
    }

    public function test_participant_can_view_users_and_events_pages(): void
    {
        $this->actingAs($this->participant);

        $this->get($this->projectUrl(ProjectMemberResource::class, $this->projectA))
            ->assertSuccessful();
        $this->get($this->projectUrl(ProjectEventResource::class, $this->projectA))
            ->assertSuccessful();
    }

    public function test_bare_org_member_cannot_edit_a_project(): void
    {
        $this->actingAs($this->bareMember);
        Filament::setCurrentPanel(Filament::getPanel('organization'));

        $url = ProjectResource::getUrl('edit', ['record' => $this->projectA], tenant: $this->organization);

        $this->get($url)->assertForbidden();
    }

    public function test_bare_org_member_cannot_reach_create_project(): void
    {
        $this->actingAs($this->bareMember);
        Filament::setCurrentPanel(Filament::getPanel('organization'));

        $url = ProjectResource::getUrl('create', tenant: $this->organization);

        $status = $this->get($url)->status();
        $this->assertTrue(in_array($status, [403, 404], true), "expected 403 or 404, got {$status}");
    }

    public function test_outsider_cannot_reach_create_project(): void
    {
        $this->actingAs($this->outsider);
        Filament::setCurrentPanel(Filament::getPanel('organization'));

        $url = ProjectResource::getUrl('create', tenant: $this->organization);

        $status = $this->get($url)->status();
        $this->assertTrue(in_array($status, [403, 404], true), "expected 403 or 404, got {$status}");
    }

    public function test_participant_cannot_reach_create_project_via_url(): void
    {
        $this->actingAs($this->participant);
        Filament::setCurrentPanel(Filament::getPanel('organization'));

        $url = ProjectResource::getUrl('create', tenant: $this->organization);

        $status = $this->get($url)->status();
        $this->assertTrue(in_array($status, [403, 404], true), "expected 403 or 404, got {$status}");
    }

    public function test_participant_member_table_hides_invite_action(): void
    {
        $this->actingAs($this->participant);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->projectA);

        Livewire::test(ListProjectMembers::class)
            ->assertOk()
            ->assertActionHidden('create');
    }

    public function test_project_admin_member_table_shows_invite_action(): void
    {
        $this->actingAs($this->projectAdmin);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->projectA);

        Livewire::test(ListProjectMembers::class)
            ->assertOk()
            ->assertActionVisible('create');
    }

    public function test_participant_cannot_load_edit_project_page(): void
    {
        $this->actingAs($this->participant);
        Filament::setCurrentPanel(Filament::getPanel('organization'));

        $url = ProjectResource::getUrl('edit', ['record' => $this->projectA], tenant: $this->organization);

        $this->get($url)->assertForbidden();
    }

    public function test_participant_cannot_edit_project(): void
    {
        $this->actingAs($this->participant);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);

        $this->assertFalse(ProjectResource::canEdit($this->projectA));
    }

    public function test_participant_project_resource_cannot_create(): void
    {
        $this->actingAs($this->participant);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);

        $this->assertFalse(ProjectResource::canCreate());
    }
}
