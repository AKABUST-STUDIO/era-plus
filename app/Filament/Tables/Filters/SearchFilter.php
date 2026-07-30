<?php

namespace App\Filament\Tables\Filters;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class SearchFilter
{
    public static function make(string $placeholder = 'Search…'): Filter
    {
        return Filter::make('search')
            ->form([
                TextInput::make('query')
                    ->hiddenLabel()
                    ->placeholder($placeholder)
                    ->prefixIcon('lucide-search')
                    ->live(debounce: 300)
                    ->default(fn (Component $livewire): string => $livewire->tableSearch ?? '')
                    ->afterStateUpdated(fn (?string $state, Component $livewire) => $livewire->tableSearch = (string) ($state ?? '')),
            ])
            ->query(fn (Builder $query): Builder => $query)
            ->indicateUsing(fn (): array => []);
    }
}
