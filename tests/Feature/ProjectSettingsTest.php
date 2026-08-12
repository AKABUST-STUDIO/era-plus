<?php

namespace Tests\Feature;

use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ErasmusKeyAction;
use App\Enums\Project\ErasmusManagingBody;
use App\Enums\Project\ErasmusPriority;
use App\Enums\Project\ProjectStatus;
use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Filament\Project\Settings\Pages\GeneralSettings;
use App\Http\Middleware\ApplyTenantContext;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\ErasmusPriority as ErasmusPriorityModel;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->user->joinOrganization($this->organization);
        $this->project = Project::factory()->for($this->organization)->create([
            'name' => 'Original',
            'beginning_date' => '2026-09-01',
        ]);
        $this->user->joinProject($this->project);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project.settings'));
        OrganizationService::remember($this->organization);
        ProjectService::remember($this->project);
        URL::defaults([
            'organization' => $this->organization->slug,
            'project' => $this->project->slug,
        ]);
    }

    private function url(string $path): string
    {
        return 'http://app.'.parse_url(config('app.url'), PHP_URL_HOST).$path;
    }

    private function settingsUrl(?Project $project = null): string
    {
        $project ??= $this->project;

        return $this->url('/'.$project->organization->slug.'/'.$project->slug.'/settings/overview');
    }

    public function test_settings_panel_serves_the_page_to_a_project_member(): void
    {
        $this->get($this->settingsUrl())->assertSuccessful();
    }

    public function test_settings_panel_403s_for_a_user_who_is_not_a_project_member(): void
    {
        $outsider = User::factory()->create();
        $outsider->joinOrganization($this->organization);
        $outsider->joinProject(Project::factory()->for($this->organization)->create());

        $this->actingAs($outsider)
            ->get($this->settingsUrl())
            ->assertForbidden();
    }

    public function test_settings_panel_404s_when_the_project_belongs_to_another_organization(): void
    {
        $otherOrganization = Organization::factory()->create();
        $this->user->joinOrganization($otherOrganization);

        $this->get($this->url('/'.$otherOrganization->slug.'/'.$this->project->slug.'/settings/overview'))
            ->assertNotFound();
    }

    public function test_settings_panel_redirects_an_unauthenticated_request_to_login(): void
    {
        auth()->logout();

        $response = $this->get($this->settingsUrl());

        $response->assertRedirect();
        $this->assertStringContainsString('/login', (string) $response->headers->get('Location'));
    }

    public function test_project_panel_context_can_link_to_the_settings_panel(): void
    {
        URL::defaults([]);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);

        app(ApplyTenantContext::class)->handle(
            Request::create($this->url('/'.$this->organization->slug.'/'.$this->project->slug)),
            fn (): Response => new Response,
        );

        $this->assertSame(
            $this->settingsUrl(),
            GeneralSettings::getUrl(panel: 'project.settings'),
        );
    }

    public function test_settings_page_renders(): void
    {
        Livewire::test(GeneralSettings::class)->assertSuccessful();
    }

    public function test_can_save_details(): void
    {
        Livewire::test(GeneralSettings::class)
            ->fillForm([
                'name' => 'Renamed',
                'slug' => 'renamed',
                'project_reference' => '2026-1-EE01-KA122-SCH-000001',
                'status' => ProjectStatus::Running->value,
            ])
            ->callAction(TestAction::make('saveDetails')->schemaComponent('details-section', 'form'));

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'name' => 'Renamed',
            'slug' => 'renamed',
            'project_reference' => '2026-1-EE01-KA122-SCH-000001',
            'status' => ProjectStatus::Running->value,
        ]);
    }

    public function test_saving_a_new_slug_redirects_to_the_new_url(): void
    {
        Livewire::test(GeneralSettings::class)
            ->fillForm([
                'name' => 'Renamed',
                'slug' => 'renamed',
                'status' => ProjectStatus::Running->value,
            ])
            ->callAction(TestAction::make('saveDetails')->schemaComponent('details-section', 'form'))
            ->assertRedirect(GeneralSettings::getUrl(['project' => 'renamed']));
    }

    public function test_can_save_programme(): void
    {
        $priority = ErasmusPriorityModel::query()
            ->whereNull('organization_id')
            ->where('name', ErasmusPriority::InclusionAndDiversity->getLabel())
            ->firstOrFail();

        Livewire::test(GeneralSettings::class)
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
    }

    public function test_can_save_dates(): void
    {
        Livewire::test(GeneralSettings::class)
            ->fillForm([
                'beginning_date' => '2026-10-01',
                'end_date' => '2027-03-31',
            ])
            ->callAction(TestAction::make('saveDates')->schemaComponent('dates-section', 'form'));

        $project = $this->project->fresh();

        $this->assertSame('2026-10-01', $project->beginning_date->toDateString());
        $this->assertSame('2027-03-31', $project->end_date->toDateString());
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        Livewire::test(GeneralSettings::class)
            ->fillForm([
                'beginning_date' => '2026-09-01',
                'end_date' => '2026-08-01',
            ])
            ->callAction(TestAction::make('saveDates')->schemaComponent('dates-section', 'form'))
            ->assertHasFormErrors(['end_date']);
    }

    public function test_can_delete_project(): void
    {
        Livewire::test(GeneralSettings::class)
            ->callAction(TestAction::make('delete')->schemaComponent('danger-section', 'form'));

        $this->assertDatabaseMissing('projects', ['id' => $this->project->id]);
        $this->assertNull(ProjectService::selected());
    }

    public function test_breadcrumbs_link_back_to_the_project(): void
    {
        $breadcrumbs = Livewire::test(GeneralSettings::class)
            ->instance()
            ->getBreadcrumbs();

        $values = array_values($breadcrumbs);
        $this->assertCount(3, $values);
        $this->assertSame($this->project->name, $values[0]);
        $this->assertSame(__('settings.breadcrumb'), $values[1]);
        $this->assertSame(__('forms.project.settings.title'), $values[2]);
        $this->assertSame(
            ProjectService::urlFor($this->project),
            array_key_first($breadcrumbs),
        );
    }
}
