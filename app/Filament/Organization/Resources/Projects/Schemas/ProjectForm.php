<?php

namespace App\Filament\Organization\Resources\Projects\Schemas;

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
                    ->label('Beginning date'),
                DatePicker::make('end_date')
                    ->label('End date')
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
            ]);
    }
}
