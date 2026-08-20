<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('project admin sees the invite button on project members page', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->for($organization)->create();
    $admin = User::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);
    $admin->joinProject($project, ProjectRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization, $project): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/'.$project->slug.'/users')
            ->waitForText('Users')
            ->assertSee('Send invitation');
    });
});

test('project participant does not see the invite button', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->for($organization)->create();
    $participant = User::factory()->create();
    $participant->joinOrganization($organization, OrganizationRole::Member);
    $participant->joinProject($project, ProjectRole::Participant);

    $this->browse(function (Browser $browser) use ($participant, $organization, $project): void {
        $browser->loginAs($participant)
            ->visit('/'.$organization->slug.'/'.$project->slug.'/users')
            ->waitForText('Users')
            ->assertDontSee('Send invitation');
    });
});
