<?php

namespace App\Filament\Tables\Filters;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->prefixIcon(fn (?string $state): ?string => filled($state) ? null : 'lucide-search')
                    ->prefixAction(
                        Action::make('clearSearch')
                            ->label(__('forms.common.clear'))
                            ->icon('lucide-x')
                            ->color('gray')
                            ->iconButton()
                            ->visible(fn (?string $state): bool => filled($state))
                            ->action(function (Set $set, Component $livewire): void {
                                $set('query', '');
                                $livewire->tableSearch = '';
                            }),
                    )
                    ->live(debounce: 300)
                    ->default(fn (Component $livewire): string => $livewire->tableSearch ?? '')
                    ->afterStateUpdated(fn (?string $state, Component $livewire) => $livewire->tableSearch = (string) ($state ?? '')),
            ])
            ->query(fn (Builder $query): Builder => $query)
            ->indicateUsing(fn (): array => []);
    }
}
