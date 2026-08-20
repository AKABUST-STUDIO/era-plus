<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('user cannot open a project from another organization', function (): void {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $projectB = Project::factory()->for($orgB)->create();

    $user = User::factory()->create();
    $user->joinOrganization($orgA, OrganizationRole::Admin);

    $this->browse(function (Browser $browser) use ($user, $orgA, $projectB): void {
        $browser->loginAs($user)
            ->visit('/'.$orgA->slug.'/'.$projectB->slug.'/overview');

        $browser->pause(500);

        $body = (string) $browser->script('return document.body ? document.body.innerText : "";')[0];
        expect($body)->toMatch('/Not Found|Forbidden|403|404/');
    });
});

test('project participant cannot access sibling project via url', function (): void {
    $organization = Organization::factory()->create();
    $mine = Project::factory()->for($organization)->create();
    $other = Project::factory()->for($organization)->create();

    $participant = User::factory()->create();
    $participant->joinOrganization($organization, OrganizationRole::Member);
    $participant->joinProject($mine, ProjectRole::Participant);

    $this->browse(function (Browser $browser) use ($participant, $organization, $other): void {
        $browser->loginAs($participant)
            ->visit('/'.$organization->slug.'/'.$other->slug.'/users');

        $browser->pause(500);

        $body = (string) $browser->script('return document.body ? document.body.innerText : "";')[0];
        expect($body)->toMatch('/Forbidden|Not Found|403|404/');
    });
});
