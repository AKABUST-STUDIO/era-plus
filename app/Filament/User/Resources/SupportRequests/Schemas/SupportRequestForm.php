<?php

namespace App\Filament\User\Resources\SupportRequests\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SupportRequestForm
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
            TextInput::make('subject')
                ->hiddenLabel()
                ->placeholder(__('user.support.subject'))
                ->required()
                ->maxLength(255),
            Textarea::make('body')
                ->hiddenLabel()
                ->placeholder(__('user.support.body'))
                ->required()
                ->rows(6),
            Placeholder::make('email_note')
                ->hiddenLabel()
                ->content(new HtmlString(__('user.support.new.email_note'))),
        ];
    }
}
