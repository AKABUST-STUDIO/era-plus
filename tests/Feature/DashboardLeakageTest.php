<?php

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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->project = Project::factory()->for($this->organization)->create();

    OrganizationService::remember($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);
});

test('activity page denied for bare org member', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    $this->actingAs($member);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);

    $this->assertFalse(Gate::forUser($member)->allows('view', ActivityLog::class));
    $this->assertFalse(Activity::canAccess());
});

test('activity page denied for project participant', function (): void {
    $participant = User::factory()->create();
    $participant->joinOrganization($this->organization, OrganizationRole::Member);
    $participant->joinProject($this->project, ProjectRole::Participant);

    $this->actingAs($participant);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);

    $this->assertFalse(Activity::canAccess());
});

test('activity page allowed for org admin', function (): void {
    $admin = User::factory()->create();
    $admin->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);

    $this->assertTrue(Activity::canAccess());
});

test('activity url returns forbidden for bare member', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);

    $this->actingAs($member);
    Filament::setCurrentPanel(Filament::getPanel('organization'));

    $url = Activity::getUrl(tenant: $this->organization);
    $status = $this->get($url)->status();
    $this->assertTrue(in_array($status, [403, 404], true), "activity URL for bare member: {$status}");
});

test('member role does not include activity permission', function (): void {
    $this->assertNotContains(
        ActivityPermission::View->value,
        OrganizationRole::Member->defaultPermissions()
    );
});
