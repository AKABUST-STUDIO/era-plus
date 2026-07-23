<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\Project\ProjectRole;
use App\Filament\Project\Pages\Overview;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Models\Organization;
use App\Providers\Filament\ProjectPanelProvider;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;

class CreateProject extends CreateRecord
{
    use HasWizard {
        getWizardComponent as protected baseWizardComponent;
    }

    protected static string $resource = ProjectResource::class;

    public function getWizardComponent(): Component
    {
        /** @var Wizard $wizard */
        $wizard = $this->baseWizardComponent();

        return $wizard
            ->persistStepInQueryString()
            ->hiddenHeader()
            ->cancelAction(null);
    }

    /**
     * @return array<int, Step>
     */
    protected function getSteps(): array
    {
        return [
            Step::make(__('forms.project.create_wizard.programme_step'))
                ->description(__('forms.project.create_wizard.programme_step_description'))
                ->columns(4)
                ->schema(ProjectForm::programmeComponents()),
            Step::make(__('forms.project.create_wizard.details_step'))
                ->description(__('forms.project.create_wizard.details_step_description'))
                ->columns(4)
                ->schema(ProjectForm::detailsComponents()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Organization && empty($data['organization_id'])) {
            $data['organization_id'] = $tenant->id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return Overview::getUrl(panel: ProjectPanelProvider::PANEL_ID, tenant: $this->record);
    }

    protected function afterCreate(): void
    {
        auth()->user()->joinProject($this->record, ProjectRole::Admin);
    }
}
