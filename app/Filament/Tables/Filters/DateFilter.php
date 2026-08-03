<?php

namespace App\Filament\Tables\Filters;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Set;

class DateFilter
{
    public static function make(string $name, string $placeholder, ?string $icon = null): DatePicker
    {
        return DatePicker::make($name)
            ->hiddenLabel()
            ->validationAttribute($placeholder)
            ->placeholder($placeholder)
            ->prefixIcon($icon)
            ->native(false)
            ->live()
            ->suffixAction(
                Action::make('clear'.ucfirst($name))
                    ->label(__('forms.common.clear'))
                    ->icon('lucide-x')
                    ->color('gray')
                    ->iconButton()
                    ->visible(fn (?string $state): bool => filled($state))
                    ->action(fn (Set $set) => $set($name, null)),
            );
    }
}
