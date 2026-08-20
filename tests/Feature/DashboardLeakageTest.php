<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Permissions\ActivityPermission;
use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Pages\Activity;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DashboardLeakageTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->project = Project::factory()->for($this->organization)->create();

        OrganizationService::remember($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_activity_page_denied_for_bare_org_member(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->actingAs($member);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);

        $this->assertFalse(Gate::forUser($member)->allows('view', ActivityLog::class));
        $this->assertFalse(Activity::canAccess());
    }

    public function test_activity_page_denied_for_project_participant(): void
    {
        $participant = User::factory()->create();
        $participant->joinOrganization($this->organization, OrganizationRole::Member);
        $participant->joinProject($this->project, ProjectRole::Participant);

        $this->actingAs($participant);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);

        $this->assertFalse(Activity::canAccess());
    }

    public function test_activity_page_allowed_for_org_admin(): void
    {
        $admin = User::factory()->create();
        $admin->joinOrganization($this->organization, OrganizationRole::Admin);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);

        $this->assertTrue(Activity::canAccess());
    }

    public function test_activity_url_returns_forbidden_for_bare_member(): void
    {
        $member = User::factory()->create();
        $member->joinOrganization($this->organization, OrganizationRole::Member);

        $this->actingAs($member);
        Filament::setCurrentPanel(Filament::getPanel('organization'));

        $url = Activity::getUrl(tenant: $this->organization);
        $status = $this->get($url)->status();
        $this->assertTrue(in_array($status, [403, 404], true), "activity URL for bare member: {$status}");
    }

    public function test_member_role_does_not_include_activity_permission(): void
    {
        $this->assertNotContains(
            ActivityPermission::View->value,
            OrganizationRole::Member->defaultPermissions()
        );
    }
}
