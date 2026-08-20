<?php

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Pages\Activity as OrganizationActivity;
use App\Filament\Project\Pages\Activity as ProjectActivity;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\ProjectEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create(['name' => 'Maria']);
    $this->organization = Organization::factory()->create();
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
    $this->project = Project::factory()->for($this->organization)->create();
    $this->user->joinProject($this->project);

    $this->actingAs($this->user);
});

test('record captures actor and scope', function (): void {
    $entry = ActivityLog::record(
        organization: $this->organization,
        label: 'Updated organization name',
        project: null,
        eventType: 'organization.updated',
        target: $this->organization,
        data: ['from' => 'old', 'to' => 'new'],
    );

    $this->assertSame($this->organization->id, $entry->organization_id);
    $this->assertSame($this->user->id, $entry->causer_id);
    $this->assertSame('organization.updated', $entry->event);
    $this->assertSame(['from' => 'old', 'to' => 'new'], $entry->fresh()->properties->toArray());
});

test('org activity page lists org entries only', function (): void {
    ActivityLog::record($this->organization, 'A happened');
    $otherOrg = Organization::factory()->create();
    ActivityLog::record($otherOrg, 'Stranger event');

    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);

    Livewire::test(OrganizationActivity::class)
        ->assertCanSeeTableRecords(
            ActivityLog::query()->where('organization_id', $this->organization->id)->get()
        )
        ->assertCanNotSeeTableRecords(
            ActivityLog::query()->where('organization_id', $otherOrg->id)->get()
        );
});

test('project activity page lists project entries only', function (): void {
    ActivityLog::record($this->organization, 'Project event', $this->project);
    ActivityLog::record($this->organization, 'Org-only event');

    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->project);
    URL::defaults(['organization' => $this->organization->slug]);

    Livewire::test(ProjectActivity::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(
            ActivityLog::query()->where('project_id', $this->project->id)->get()
        )
        ->assertCanNotSeeTableRecords(
            ActivityLog::query()->whereNull('project_id')->get()
        );
});

test('model events are scoped to the organization and project', function (): void {
    $event = ProjectEvent::factory()->for($this->project)->create();

    $entry = ActivityLog::query()
        ->where('subject_type', ProjectEvent::class)
        ->where('subject_id', $event->id)
        ->sole();

    $this->assertSame('created', $entry->event);
    $this->assertSame($this->project->id, $entry->project_id);
    $this->assertSame($this->organization->id, $entry->organization_id);
});

test('project event changes are logged and visible on the project page', function (): void {
    $event = ProjectEvent::factory()->for($this->project)->create();
    $event->update(['title' => 'Kickoff meeting']);

    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->project);
    URL::defaults(['organization' => $this->organization->slug]);

    Livewire::test(ProjectActivity::class)
        ->assertCanSeeTableRecords(
            ActivityLog::query()->where('subject_type', ProjectEvent::class)->get()
        );

    $this->assertSame(
        2,
        ActivityLog::query()->where('subject_type', ProjectEvent::class)->count(),
    );
});

test('google sync columns alone do not log an update', function (): void {
    $event = ProjectEvent::factory()->for($this->project)->create();

    $event->forceFill([
        'google_event_id' => 'abc123',
        'google_updated_at' => now(),
    ])->save();

    $this->assertSame(
        ['created'],
        ActivityLog::query()
            ->where('subject_type', ProjectEvent::class)
            ->pluck('event')
            ->all(),
    );
});

test('project activity page is hidden without the activity permission', function (): void {
    $member = User::factory()->create();
    $member->joinOrganization($this->organization, OrganizationRole::Member);
    $member->joinProject($this->project);

    $this->actingAs($member);

    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->project);
    URL::defaults(['organization' => $this->organization->slug]);

    $this->assertFalse(ProjectActivity::canAccess());
});

test('date filter narrows activity to the range', function (): void {
    $early = ActivityLog::record($this->organization, 'Early event');
    $early->forceFill(['created_at' => '2026-01-10 09:00:00'])->save();

    $late = ActivityLog::record($this->organization, 'Late event');
    $late->forceFill(['created_at' => '2026-06-20 09:00:00'])->save();

    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);

    Livewire::test(OrganizationActivity::class)
        ->filterTable('group', ['from' => '2026-05-01'])
        ->assertCanSeeTableRecords([$late])
        ->assertCanNotSeeTableRecords([$early])
        ->filterTable('group', ['from' => null, 'until' => '2026-02-01'])
        ->assertCanSeeTableRecords([$early])
        ->assertCanNotSeeTableRecords([$late]);
});
