<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Filament\Organization\Widgets\OrganizationStatsWidget;
use App\Filament\Project\Widgets\ProjectStatsWidget;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use App\Models\Project\ProjectEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
    $this->actingAs($this->user);
    URL::defaults(['organization' => $this->organization->slug]);
});

function ensureCountryForDashboard(): int
{
    $id = DB::table('countries')->value('id');

    if ($id !== null) {
        return (int) $id;
    }

    DB::table('countries')->insert([
        'iso2' => 'ES',
        'name' => 'Spain',
        'status' => 1,
        'phone_code' => '0',
        'iso3' => 'ESP',
        'region' => 'Europe',
        'subregion' => 'Europe',
    ]);

    return (int) DB::table('countries')->where('iso2', 'ES')->value('id');
}

test('organization stats widget shows counts of projects members and pending invites', function (): void {
    Project::factory()->for($this->organization)->count(3)->create();

    $member = User::factory()->create();
    $member->joinOrganization($this->organization);

    $pending = User::factory()->unverified()->create();
    $pending->joinOrganization($this->organization);

    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);

    Livewire::test(OrganizationStatsWidget::class)
        ->assertSee((string) Project::query()->where('organization_id', $this->organization->id)->count())
        ->assertSee((string) $this->organization->users()->whereNotNull('email_verified_at')->count())
        ->assertSee((string) $this->organization->users()->whereNull('email_verified_at')->count());
});

test('project stats widget shows participant and upcoming event counts', function (): void {
    $project = Project::factory()->for($this->organization)->create([
        'end_date' => now()->addDays(60)->toDateString(),
    ]);
    $this->user->joinProject($project, ProjectRole::Admin);

    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($project);

    $countryId = ensureCountryForDashboard();
    $sending = ParticipantOrganization::create(['name' => 'Universidad']);
    $participant = Participant::factory()->create();
    $project->addParticipant($participant, $countryId, $sending);

    ProjectEvent::create([
        'project_id' => $project->id,
        'title' => 'Kickoff',
        'starts_at' => now()->addDay()->toDateTimeString(),
        'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
    ]);

    Livewire::test(ProjectStatsWidget::class)
        ->assertSee('1')
        ->assertSee(__('dashboard.project.participants'))
        ->assertSee(__('dashboard.project.upcoming_events'))
        ->assertSee(__('dashboard.project.days_remaining'));
});

test('project stats widget renders unknown when the project has no end date', function (): void {
    $project = Project::factory()->for($this->organization)->create(['end_date' => null]);
    $this->user->joinProject($project, ProjectRole::Admin);

    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($project);

    Livewire::test(ProjectStatsWidget::class)
        ->assertSee(__('dashboard.project.days_remaining_unknown'));
});
