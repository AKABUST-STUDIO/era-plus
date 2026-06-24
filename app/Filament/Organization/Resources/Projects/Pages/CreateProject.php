<?php

namespace App\Filament\Organization\Resources\Projects\Pages;

use App\Enums\ProjectRole;
use App\Filament\Organization\Resources\Projects\ProjectResource;
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
use Filament\Schemas\Components\Wizard\Step;
use Nnjeim\World\Models\Country;

class CreateProject extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ProjectResource::class;

    /**
     * @return array<int, Step>
     */
    protected function getSteps(): array
    {
        return [
            Step::make('Details')
                ->description('Project basics')
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
                            'mobility' => 'Mobility',
                            'cooperation' => 'Cooperation partnership',
                            'small_scale' => 'Small-scale partnership',
                            'youth' => 'Youth exchange',
                        ]),
                    Textarea::make('description')
                        ->maxLength(2000)
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Step::make('Countries')
                ->description('Participating countries and travel-expense limits')
                ->schema([
                    Repeater::make('project_countries')
                        ->label(false)
                        ->dehydrated(false)
                        ->defaultItems(0)
                        ->addActionLabel('Add country')
                        ->schema([
                            Select::make('country_id')
                                ->label(__('forms.common.country'))
                                ->options(fn (): array => Country::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required()
                                ->distinct(),
                            TextInput::make('default_travel_expense_limit')
                                ->label(__('forms.common.travel_limit'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->prefix('€'),
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
        $this->record->users()->attach(auth()->id(), [
            'role' => ProjectRole::Coordinator->value,
        ]);

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
