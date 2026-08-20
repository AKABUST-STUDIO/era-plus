<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ErasmusKeyAction;
use App\Enums\Project\ErasmusManagingBody;
use App\Enums\Project\ProjectRole;
use App\Enums\Project\ProjectStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('organization'));
    Filament::setTenant($this->organization);
    URL::defaults(['organization' => $this->organization->slug]);
});

function projectWizardBaseFormData(): array
{
    return [
        'erasmus_field' => ErasmusField::Youth->value,
        'erasmus_key_action' => ErasmusKeyAction::KeyAction1->value,
        'erasmus_action' => ErasmusActionType::Ka152->value,
        'erasmus_managing_body' => ErasmusManagingBody::NationalAgency->value,
        'status' => ProjectStatus::Draft->value,
        'call_year' => '2026-01-01',
        'beginning_date' => '2026-09-01',
        'end_date' => '2027-06-30',
        'duration_months' => 12,
    ];
}

test('wizard renders', function (): void {
    Livewire::test(CreateProject::class)->assertSuccessful();
});

test('wizard creates project', function (): void {
    Livewire::test(CreateProject::class)
        ->fillForm([
            ...projectWizardBaseFormData(),
            'name' => 'Mobility 2026',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::query()->where('name', 'Mobility 2026')->firstOrFail();

    $this->assertSame(ErasmusField::Youth, $project->erasmus_field);
    $this->assertSame(ErasmusKeyAction::KeyAction1, $project->erasmus_key_action);
    $this->assertSame(ErasmusActionType::Ka152, $project->erasmus_action);
    $this->assertSame(ErasmusManagingBody::NationalAgency, $project->erasmus_managing_body);
    $this->assertSame(ProjectStatus::Draft, $project->status);
    $this->assertSame(2026, $project->call_year);
    $this->assertSame(12, $project->duration_months);
    $this->assertSame('2026-09-01', $project->beginning_date->toDateString());
    $this->assertSame('2027-06-30', $project->end_date->toDateString());
});

test('creator attached as project admin via wizard', function (): void {
    Livewire::test(CreateProject::class)
        ->fillForm([
            ...projectWizardBaseFormData(),
            'name' => 'Coordinator Test',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::query()->where('name', 'Coordinator Test')->firstOrFail();

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $this->user->id,
        'role_id' => $project->roleFor(ProjectRole::Admin)->id,
    ]);
});

test('end date must be after beginning date', function (): void {
    Livewire::test(CreateProject::class)
        ->fillForm([
            ...projectWizardBaseFormData(),
            'name' => 'Bad Dates',
            'end_date' => '2026-08-01',
        ])
        ->call('create')
        ->assertHasFormErrors(['end_date']);
});

test('duration is computed from the dates when left empty', function (): void {
    Livewire::test(CreateProject::class)
        ->set('data.beginning_date', '2026-09-01')
        ->set('data.end_date', '2027-06-30')
        ->assertSchemaStateSet(['duration_months' => 10]);
});

test('manually entered duration is not overwritten by the dates', function (): void {
    Livewire::test(CreateProject::class)
        ->set('data.duration_months', 18)
        ->set('data.beginning_date', '2026-09-01')
        ->set('data.end_date', '2027-06-30')
        ->assertSchemaStateSet(['duration_months' => 18]);
});

test('call year defaults to the current year', function (): void {
    Livewire::test(CreateProject::class)
        ->assertSchemaStateSet(['call_year' => (string) now()->year]);
});

test('call year is stored as a year', function (): void {
    Livewire::test(CreateProject::class)
        ->fillForm([
            ...projectWizardBaseFormData(),
            'name' => 'Call Year Test',
            'call_year' => '2027-05-14',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertSame(2027, Project::query()->where('name', 'Call Year Test')->firstOrFail()->call_year);
});

test('key action survives switching to a field that still offers it', function (): void {
    Livewire::test(CreateProject::class)
        ->set('data.erasmus_field', ErasmusField::SchoolEducation->value)
        ->set('data.erasmus_key_action', ErasmusKeyAction::KeyAction2->value)
        ->set('data.erasmus_action', ErasmusActionType::Ka220->value)
        ->set('data.erasmus_field', ErasmusField::AdultEducation->value)
        ->assertSchemaStateSet([
            'erasmus_key_action' => ErasmusKeyAction::KeyAction2->value,
            'erasmus_action' => ErasmusActionType::Ka220->value,
        ]);
});

test('duration must sit inside the action type range', function (): void {
    Livewire::test(CreateProject::class)
        ->fillForm([
            ...projectWizardBaseFormData(),
            'name' => 'Too Long',
            'duration_months' => 30,
        ])
        ->call('create')
        ->assertHasFormErrors(['duration_months']);
});

test('action type options are limited to the chosen field and key action', function (): void {
    $options = ErasmusActionType::optionsFor(ErasmusField::HigherEducation, ErasmusKeyAction::KeyAction1);

    $this->assertSame(['KA131', 'KA171'], array_keys($options));
});
