<?php

namespace App\Filament\Project\Resources\ProjectEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required()->maxLength(255),
                DateTimePicker::make('starts_at')->label('Starts')->required(),
                DateTimePicker::make('ends_at')->label('Ends')->after('starts_at'),
                Toggle::make('all_day')->label('All day'),
                TextInput::make('location')->maxLength(255),
                Textarea::make('description')
                    ->maxLength(2000)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
