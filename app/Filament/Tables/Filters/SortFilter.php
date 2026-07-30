<?php

namespace App\Filament\Tables\Filters;

use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class SortFilter
{
    /**
     * @param  array<string, string>  $options
     */
    public static function make(array $options, ?string $placeholder = null): Filter
    {
        return Filter::make('sort')
            ->form([
                Select::make('sort')
                    ->hiddenLabel()
                    ->placeholder($placeholder ?? 'Sort by…')
                    ->prefixIcon('lucide-arrow-up-down')
                    ->options($options)
                    ->native(false)
                    ->live()
                    ->default(fn (Component $livewire): ?string => $livewire->tableSort)
                    ->afterStateUpdated(fn (?string $state, Component $livewire) => $livewire->tableSort = $state),
            ])
            ->query(fn (Builder $query): Builder => $query)
            ->indicateUsing(fn (): array => []);
    }
}
