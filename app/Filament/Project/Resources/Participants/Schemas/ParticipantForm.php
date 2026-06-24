<?php

namespace App\Filament\Project\Resources\Participants\Schemas;

use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Arrayable;

class ParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('country_id')
                    ->label(__('forms.common.country'))
                    ->options(fn (): Arrayable => self::projectCountryOptions())
                    ->required()
                    ->searchable(),
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

    /**
     * @return Arrayable<int, string>
     */
    private static function projectCountryOptions(): Arrayable
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return collect();
        }

        return $project->countries()
            ->with('country')
            ->get()
            ->mapWithKeys(fn ($pc) => [$pc->country_id => $pc->country->name]);
    }
}
