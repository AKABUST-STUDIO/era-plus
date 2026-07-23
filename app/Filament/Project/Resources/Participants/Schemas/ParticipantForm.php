<?php

namespace App\Filament\Project\Resources\Participants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('last_name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
            ]);
    }
}
