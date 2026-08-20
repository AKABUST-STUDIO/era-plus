<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('bare org member sees no projects in the switcher', function (): void {
    $organization = Organization::factory()->create();
    Project::factory()->for($organization)->create(['name' => 'Hidden Project']);

    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);

    $this->browse(function (Browser $browser) use ($member, $organization): void {
        $browser->loginAs($member)
            ->visit('/'.$organization->slug.'/projects')
            ->waitForText('Projects')
            ->assertDontSee('Hidden Project');
    });
});

test('participant sees only their project in the switcher', function (): void {
    $organization = Organization::factory()->create();
    $mine = Project::factory()->for($organization)->create(['name' => 'My Project']);
    Project::factory()->for($organization)->create(['name' => 'Other Project']);

    $participant = User::factory()->create();
    $participant->joinOrganization($organization, OrganizationRole::Member);
    $participant->joinProject($mine, ProjectRole::Participant);

    $this->browse(function (Browser $browser) use ($participant, $organization): void {
        $browser->loginAs($participant)
            ->visit('/'.$organization->slug.'/projects')
            ->waitForText('Projects')
            ->assertDontSee('Other Project');
    });
});

test('org admin can open the create project page', function (): void {
    $organization = Organization::factory()->create();

    $admin = User::factory()->create();
    $admin->joinOrganization($organization, OrganizationRole::Admin);

    $this->browse(function (Browser $browser) use ($admin, $organization): void {
        $browser->loginAs($admin)
            ->visit('/'.$organization->slug.'/projects/create')
            ->waitForText('Create Project');
    });
});

test('org member cannot open the create project page', function (): void {
    $organization = Organization::factory()->create();

    $member = User::factory()->create();
    $member->joinOrganization($organization, OrganizationRole::Member);

    $this->browse(function (Browser $browser) use ($member, $organization): void {
        $status = (int) $browser->loginAs($member)
            ->visit('/'.$organization->slug.'/projects/create')
            ->script('return document.title.includes("Forbidden") || document.body.textContent.includes("403") || document.body.textContent.includes("404") ? 403 : 200;')[0];

        expect($status)->toBe(403);
    });
});
