<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('project admin lands on the settings page', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->for($organization)->create(['name' => 'Test Project']);
    $admin = User::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);
    $admin->joinProject($project, ProjectRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization, $project): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/'.$project->slug.'/settings/overview')
            ->waitForText('Project')
            ->assertSee('Project');
    });
});

test('delete project confirmation refuses a mismatched name and phrase', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->for($organization)->create(['name' => 'Alpha']);
    $admin = User::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);
    $admin->joinProject($project, ProjectRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization, $project): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/'.$project->slug.'/settings/overview')
            ->waitFor('[data-section-key="danger-section"], .fi-section', 5)
            ->pressAndWaitFor('Delete project', 3)
            ->waitFor('.fi-modal', 5)
            ->type('name_confirm', 'wrong name')
            ->type('phrase_confirm', 'wrong phrase')
            ->pressAndWaitFor('Delete project', 3)
            ->pause(500);

        $body = (string) $browser->script('return document.body.innerText;')[0];

        expect($body)->toContain('does not match');

        expect(Project::query()->where('id', $project->id)->whereNull('deleted_at')->exists())->toBeTrue();
    });
});

test('outsider cannot view project settings', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->for($organization)->create();
    $outsider = User::factory()->create();
    $outsider->joinOrganization($organization, OrganizationRole::Member);
    $outsider->joinProject(Project::factory()->for($organization)->create());

    $this->browse(function (Browser $browser) use ($outsider, $organization, $project): void {
        $browser->loginAs($outsider)
            ->visit('/'.$organization->slug.'/'.$project->slug.'/settings/overview');

        $browser->pause(500);
        $body = (string) $browser->script('return document.body.innerText;')[0];
        expect($body)->toMatch('/Forbidden|Not Found|403|404/');
    });
});
