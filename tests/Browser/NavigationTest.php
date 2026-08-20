<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('admin sidebar shows all top-level items', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/projects')
            ->waitForText('Projects')
            ->assertSee('Projects')
            ->assertSee('Users')
            ->assertSee('Participants')
            ->assertSee('Events')
            ->assertSee('Activity')
            ->assertSee('Settings');
    });
});

test('member sidebar hides admin-only items', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);

    $this->browse(function (Browser $browser) use ($member, $organization): void {
        $browser->loginAs($member)
            ->visit('/'.$organization->slug.'/projects')
            ->waitForText('Projects')
            ->assertSee('Projects')
            ->assertSee('Users')
            ->assertDontSee('Activity');
    });
});

test('participant sidebar in project panel shows project items only', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->for($organization)->create(['name' => 'My Project']);
    $participant = User::factory()->create();
    $participant->joinOrganization($organization, OrganizationRole::Member);
    $participant->joinProject($project, ProjectRole::Participant);

    $this->browse(function (Browser $browser) use ($participant, $organization, $project): void {
        $browser->loginAs($participant)
            ->visit('/'.$organization->slug.'/'.$project->slug.'/users')
            ->waitForText('Users')
            ->assertSee('Users')
            ->assertSee('Events');
    });
});
