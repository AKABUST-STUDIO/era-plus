<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ErasmusKeyAction;
use App\Enums\Project\ErasmusManagingBody;
use App\Enums\Project\ProjectRole;
use App\Enums\Project\ProjectStatus;
use App\Enums\Subscription\SubscriptionTier;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->subscribed(SubscriptionTier::Pro)->create();
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_wizard_renders(): void
    {
        Livewire::test(CreateProject::class)->assertSuccessful();
    }

    public function test_wizard_creates_project(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                ...$this->baseFormData(),
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
    }

    public function test_creator_attached_as_project_admin_via_wizard(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                ...$this->baseFormData(),
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
    }

    public function test_end_date_must_be_after_beginning_date(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                ...$this->baseFormData(),
                'name' => 'Bad Dates',
                'end_date' => '2026-08-01',
            ])
            ->call('create')
            ->assertHasFormErrors(['end_date']);
    }

    public function test_duration_is_computed_from_the_dates_when_left_empty(): void
    {
        Livewire::test(CreateProject::class)
            ->set('data.beginning_date', '2026-09-01')
            ->set('data.end_date', '2027-06-30')
            ->assertSchemaStateSet(['duration_months' => 10]);
    }

    public function test_manually_entered_duration_is_not_overwritten_by_the_dates(): void
    {
        Livewire::test(CreateProject::class)
            ->set('data.duration_months', 18)
            ->set('data.beginning_date', '2026-09-01')
            ->set('data.end_date', '2027-06-30')
            ->assertSchemaStateSet(['duration_months' => 18]);
    }

    public function test_call_year_defaults_to_the_current_year(): void
    {
        Livewire::test(CreateProject::class)
            ->assertSchemaStateSet(['call_year' => (string) now()->year]);
    }

    public function test_call_year_is_stored_as_a_year(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                ...$this->baseFormData(),
                'name' => 'Call Year Test',
                'call_year' => '2027-05-14',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2027, Project::query()->where('name', 'Call Year Test')->firstOrFail()->call_year);
    }

    public function test_key_action_survives_switching_to_a_field_that_still_offers_it(): void
    {
        Livewire::test(CreateProject::class)
            ->set('data.erasmus_field', ErasmusField::SchoolEducation->value)
            ->set('data.erasmus_key_action', ErasmusKeyAction::KeyAction2->value)
            ->set('data.erasmus_action', ErasmusActionType::Ka220->value)
            ->set('data.erasmus_field', ErasmusField::AdultEducation->value)
            ->assertSchemaStateSet([
                'erasmus_key_action' => ErasmusKeyAction::KeyAction2->value,
                'erasmus_action' => ErasmusActionType::Ka220->value,
            ]);
    }

    public function test_duration_must_sit_inside_the_action_type_range(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                ...$this->baseFormData(),
                'name' => 'Too Long',
                'duration_months' => 30,
            ])
            ->call('create')
            ->assertHasFormErrors(['duration_months']);
    }

    public function test_action_type_options_are_limited_to_the_chosen_field_and_key_action(): void
    {
        $options = ErasmusActionType::optionsFor(ErasmusField::HigherEducation, ErasmusKeyAction::KeyAction1);

        $this->assertSame(['KA131', 'KA171'], array_keys($options));
    }

    /**
     * @return array<string, mixed>
     */
    private function baseFormData(): array
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
}
