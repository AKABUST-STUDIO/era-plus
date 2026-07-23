<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ErasmusKeyAction;
use App\Enums\Project\ErasmusManagingBody;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectEditFormTest extends TestCase
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
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);

        $this->project = Project::factory()
            ->for($this->organization)
            ->ofActionType(ErasmusActionType::Ka152)
            ->create([
                'name' => 'Cooperation 2026',
                'beginning_date' => '2026-09-01',
                'end_date' => '2028-08-31',
                'duration_months' => 24,
            ]);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_edit_form_hydrates_the_taxonomy(): void
    {
        Livewire::test(EditProject::class, ['record' => $this->project->getRouteKey()])
            ->assertSuccessful()
            ->assertSchemaStateSet([
                'erasmus_field' => ErasmusField::Youth->value,
                'erasmus_key_action' => ErasmusKeyAction::KeyAction1->value,
                'erasmus_action' => ErasmusActionType::Ka152->value,
                'erasmus_managing_body' => ErasmusManagingBody::NationalAgency->value,
            ]);
    }

    public function test_edit_form_saves_changes(): void
    {
        Livewire::test(EditProject::class, ['record' => $this->project->getRouteKey()])
            ->fillForm([
                'requested_grant' => 250000,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('250000.00', $this->project->fresh()->requested_grant);
    }
}
