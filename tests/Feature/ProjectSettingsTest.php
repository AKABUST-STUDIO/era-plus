<?php

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ErasmusKeyAction;
use App\Enums\Project\ErasmusManagingBody;
use App\Enums\Project\ErasmusPriority;
use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Filament\Project\Settings\Pages\ProjectSettings;
use App\Http\Middleware\ApplyTenantContext;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\ErasmusPriority as ErasmusPriorityModel;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
    $this->project = Project::factory()->for($this->organization)->create([
        'name' => 'Original',
        'beginning_date' => '2026-09-01',
    ]);
    $this->user->joinProject($this->project, ProjectRole::Admin);

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('project.settings'));
    OrganizationService::remember($this->organization);
    ProjectService::remember($this->project);
    URL::defaults([
        'organization' => $this->organization->slug,
        'project' => $this->project->slug,
    ]);
});

function projectSettingsBuildUrl(string $path): string
{
    return 'http://app.'.parse_url(config('app.url'), PHP_URL_HOST).$path;
}

function projectSettingsUrl(Project $project): string
{
    return projectSettingsBuildUrl('/'.$project->organization->slug.'/'.$project->slug.'/settings/overview');
}

test('settings panel serves the page to a project member', function (): void {
    $this->get(projectSettingsUrl($this->project))->assertSuccessful();
});

test('settings panel 403s for a user who is not a project member', function (): void {
    $outsider = User::factory()->create();
    $outsider->joinOrganization($this->organization);
    $outsider->joinProject(Project::factory()->for($this->organization)->create());

    $this->actingAs($outsider)
        ->get(projectSettingsUrl($this->project))
        ->assertForbidden();
});

test('settings panel 404s when the project belongs to another organization', function (): void {
    $otherOrganization = Organization::factory()->create();
    $this->user->joinOrganization($otherOrganization);

    $this->get(projectSettingsBuildUrl('/'.$otherOrganization->slug.'/'.$this->project->slug.'/settings/overview'))
        ->assertNotFound();
});

test('settings panel redirects an unauthenticated request to login', function (): void {
    auth()->logout();

    $response = $this->get(projectSettingsUrl($this->project));

    $response->assertRedirect();
    $this->assertStringContainsString('/login', (string) $response->headers->get('Location'));
});

test('project panel context can link to the settings panel', function (): void {
    URL::defaults([]);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->project);

    app(ApplyTenantContext::class)->handle(
        Request::create(projectSettingsBuildUrl('/'.$this->organization->slug.'/'.$this->project->slug)),
        fn (): Response => new Response,
    );

    $this->assertSame(
        parse_url(projectSettingsUrl($this->project), PHP_URL_PATH),
        parse_url(ProjectSettings::getUrl(panel: 'project.settings'), PHP_URL_PATH),
    );
});

test('settings page renders', function (): void {
    Livewire::test(ProjectSettings::class)->assertSuccessful();
});

test('can save name', function (): void {
    Livewire::test(ProjectSettings::class)
        ->fillForm(['name' => 'Renamed'])
        ->callAction(TestAction::make('saveName')->schemaComponent('name-section', 'form'));

    $this->assertDatabaseHas('projects', [
        'id' => $this->project->id,
        'name' => 'Renamed',
    ]);
});

test('saving a new slug redirects to the new url', function (): void {
    Livewire::test(ProjectSettings::class)
        ->fillForm(['slug' => 'renamed'])
        ->callAction(TestAction::make('saveUrl')->schemaComponent('url-section', 'form'))
        ->assertRedirect(ProjectSettings::getUrl(['project' => 'renamed']));

    $this->assertDatabaseHas('projects', [
        'id' => $this->project->id,
        'slug' => 'renamed',
    ]);
});

test('can save programme', function (): void {
    $priority = ErasmusPriorityModel::query()
        ->whereNull('organization_id')
        ->where('name', ErasmusPriority::InclusionAndDiversity->getLabel())
        ->firstOrFail();

    Livewire::test(ProjectSettings::class)
        ->fillForm([
            'erasmus_field' => ErasmusField::Youth->value,
            'erasmus_key_action' => ErasmusKeyAction::KeyAction1->value,
            'erasmus_action' => ErasmusActionType::Ka152->value,
            'erasmus_managing_body' => ErasmusManagingBody::NationalAgency->value,
            'priorities' => [$priority->id],
        ])
        ->callAction(TestAction::make('saveProgramme')->schemaComponent('programme-section', 'form'));

    $project = $this->project->fresh();

    $this->assertSame(ErasmusActionType::Ka152, $project->erasmus_action);
    $this->assertSame(ErasmusField::Youth, $project->erasmus_field);
    $this->assertSame(ErasmusKeyAction::KeyAction1, $project->erasmus_key_action);
    $this->assertSame([$priority->id], $project->priorities()->pluck('priorities.id')->all());
});

test('can save dates', function (): void {
    Livewire::test(ProjectSettings::class)
        ->fillForm([
            'beginning_date' => '2026-10-01',
            'end_date' => '2027-03-31',
        ])
        ->callAction(TestAction::make('saveDates')->schemaComponent('dates-section', 'form'));

    $project = $this->project->fresh();

    $this->assertSame('2026-10-01', $project->beginning_date->toDateString());
    $this->assertSame('2027-03-31', $project->end_date->toDateString());
});

test('end date must be after start date', function (): void {
    Livewire::test(ProjectSettings::class)
        ->fillForm([
            'beginning_date' => '2026-09-01',
            'end_date' => '2026-08-01',
        ])
        ->callAction(TestAction::make('saveDates')->schemaComponent('dates-section', 'form'))
        ->assertHasFormErrors(['end_date']);
});

test('can delete project', function (): void {
    Livewire::test(ProjectSettings::class)
        ->callAction(TestAction::make('delete')->schemaComponent('danger-section', 'form'), data: [
            'name_confirm' => $this->project->name,
            'phrase_confirm' => __('forms.project.settings.delete_confirm_phrase'),
        ])
        ->assertHasNoActionErrors();

    $this->assertSoftDeleted('projects', ['id' => $this->project->id]);
    $this->assertNull(ProjectService::selected());
});

test('delete project rejects a mismatched name confirmation', function (): void {
    Livewire::test(ProjectSettings::class)
        ->callAction(TestAction::make('delete')->schemaComponent('danger-section', 'form'), data: [
            'name_confirm' => 'not the project name',
            'phrase_confirm' => __('forms.project.settings.delete_confirm_phrase'),
        ])
        ->assertHasActionErrors(['name_confirm']);

    $this->assertDatabaseHas('projects', [
        'id' => $this->project->id,
        'deleted_at' => null,
    ]);
});

test('delete project rejects a mismatched phrase confirmation', function (): void {
    Livewire::test(ProjectSettings::class)
        ->callAction(TestAction::make('delete')->schemaComponent('danger-section', 'form'), data: [
            'name_confirm' => $this->project->name,
            'phrase_confirm' => 'wrong phrase',
        ])
        ->assertHasActionErrors(['phrase_confirm']);

    $this->assertDatabaseHas('projects', [
        'id' => $this->project->id,
        'deleted_at' => null,
    ]);
});

test('delete project rejects empty confirmations', function (): void {
    Livewire::test(ProjectSettings::class)
        ->callAction(TestAction::make('delete')->schemaComponent('danger-section', 'form'), data: [
            'name_confirm' => '',
            'phrase_confirm' => '',
        ])
        ->assertHasActionErrors(['name_confirm', 'phrase_confirm']);

    $this->assertDatabaseHas('projects', [
        'id' => $this->project->id,
        'deleted_at' => null,
    ]);
});

test('breadcrumbs link back to the project', function (): void {
    $breadcrumbs = Livewire::test(ProjectSettings::class)
        ->instance()
        ->getBreadcrumbs();

    $values = array_values($breadcrumbs);
    $this->assertCount(3, $values);
    $this->assertSame($this->project->name, $values[0]);
    $this->assertSame(__('settings.breadcrumb'), $values[1]);
    $this->assertSame(__('navigation.project'), $values[2]);
    $this->assertSame(
        ProjectService::urlFor($this->project),
        array_key_first($breadcrumbs),
    );
});
