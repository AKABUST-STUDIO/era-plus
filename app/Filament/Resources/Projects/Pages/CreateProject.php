<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectRole;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\ProjectCountry;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Nnjeim\World\Models\Country;

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

        return $wizard->persistStepInQueryString();
    }

    /**
     * @return array<int, Step>
     */
    protected function getSteps(): array
    {
        return [
            Step::make(__('forms.project.create_wizard.details_step'))
                ->description(__('forms.project.create_wizard.details_step_description'))
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->autofocus(),
                    DatePicker::make('beginning_date')
                        ->label(__('forms.common.beginning_date')),
                    DatePicker::make('end_date')
                        ->label(__('forms.common.end_date'))
                        ->afterOrEqual('beginning_date'),
                    Select::make('project_type')
                        ->options([
                            'mobility' => __('forms.project.types.mobility'),
                            'cooperation' => __('forms.project.types.cooperation'),
                            'small_scale' => __('forms.project.types.small_scale'),
                            'youth' => __('forms.project.types.youth'),
                        ]),
                    Textarea::make('description')
                        ->maxLength(2000)
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Step::make(__('forms.project.create_wizard.countries_step'))
                ->description(__('forms.project.create_wizard.countries_step_description'))
                ->schema([
                    Repeater::make('project_countries')
                        ->label(false)
                        ->dehydrated(false)
                        ->defaultItems(0)
                        ->addActionLabel(__('forms.project.create_wizard.add_country'))
                        ->schema([
                            Select::make('country_id')
                                ->label(__('forms.common.country'))
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search): array => Country::query()
                                    ->where('name', 'like', "%{$search}%")
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->pluck('name', 'id')
                                    ->all())
                                ->getOptionLabelUsing(fn ($value): ?string => Country::query()->find($value)?->name)
                                ->required()
                                ->distinct(),
                            TextInput::make('default_travel_expense_limit')
                                ->label(__('forms.common.travel_limit'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->prefix(__('forms.common.currency_prefix')),
                        ])
                        ->columns(2),
                ]),
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

    protected function afterCreate(): void
    {
        auth()->user()->joinProject($this->record, ProjectRole::Coordinator);

        foreach ($this->data['project_countries'] ?? [] as $row) {
            if (empty($row['country_id'])) {
                continue;
            }

            ProjectCountry::create([
                'project_id' => $this->record->id,
                'country_id' => $row['country_id'],
                'default_travel_expense_limit' => $row['default_travel_expense_limit'] ?? null,
            ]);
        }
    }
}
