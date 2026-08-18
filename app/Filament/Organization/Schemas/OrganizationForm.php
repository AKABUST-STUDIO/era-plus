<?php

namespace App\Filament\Organization\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::fields());
    }

    /**
     * @return array<int, Component>
     */
    public static function fields(): array
    {
        return [
            Section::make(__('organization.register.label'))
                ->contained(false)
                ->schema(self::innerFields()),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function innerFields(): array
    {
        return [
            TextInput::make('name')
                ->hiddenLabel()
                ->placeholder(__('organization.register.name_placeholder'))
                ->autofocus()
                ->required()
                ->maxLength(255),
        ];
    }
}
