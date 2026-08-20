<?php

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
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
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
});

test('edit form hydrates the taxonomy', function (): void {
    Livewire::test(EditProject::class, ['record' => $this->project->getRouteKey()])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'erasmus_field' => ErasmusField::Youth->value,
            'erasmus_key_action' => ErasmusKeyAction::KeyAction1->value,
            'erasmus_action' => ErasmusActionType::Ka152->value,
            'erasmus_managing_body' => ErasmusManagingBody::NationalAgency->value,
        ]);
});

test('edit form saves changes', function (): void {
    Livewire::test(EditProject::class, ['record' => $this->project->getRouteKey()])
        ->fillForm([
            'requested_grant' => 250000,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->project->fresh()->requested_grant)->toBe('250000.00');
});
