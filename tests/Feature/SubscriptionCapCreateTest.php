<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Resources\Projects\ProjectResource;
use App\Livewire\ProjectMenu;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionCapCreateTest extends TestCase
{
    use RefreshDatabase;

    private function actAsOrgAdminIn(Organization $organization): User
    {
        $admin = User::factory()->create();
        $admin->joinOrganization($organization, OrganizationRole::Admin);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($organization);
        OrganizationService::remember($organization);
        URL::defaults(['organization' => $organization->slug]);

        return $admin;
    }

    public function test_org_admin_can_create_when_below_cap(): void
    {
        $organization = Organization::factory()->create();
        $this->actAsOrgAdminIn($organization);

        $this->assertTrue(ProjectResource::canCreate());
    }

    public function test_org_admin_cannot_create_when_at_project_cap(): void
    {
        $organization = Organization::factory()->create();
        Project::factory()->for($organization)->count($organization->projectLimit())->create();

        $this->actAsOrgAdminIn($organization);

        $this->assertFalse(ProjectResource::canCreate());
    }

    public function test_project_menu_hides_create_url_when_at_cap_for_admin(): void
    {
        $organization = Organization::factory()->create();
        Project::factory()->for($organization)->count($organization->projectLimit())->create();

        $this->actAsOrgAdminIn($organization);

        $test = Livewire::test(ProjectMenu::class)->assertOk();

        $this->assertNull($test->viewData('createUrl'));
    }

    public function test_direct_hit_to_create_project_url_at_cap_is_blocked(): void
    {
        $organization = Organization::factory()->create();
        Project::factory()->for($organization)->count($organization->projectLimit())->create();

        $this->actAsOrgAdminIn($organization);

        $url = ProjectResource::getUrl('create', tenant: $organization);
        $status = $this->get($url)->status();
        $this->assertTrue(in_array($status, [403, 404], true), "expected 403|404 at cap, got {$status}");
    }
}
