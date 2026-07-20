<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }
}
