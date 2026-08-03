<?php

namespace App\Filament\Tables\Filters;

use App\Filament\Forms\Components\ColumnManagerDropdown;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class ColumnManagerFilter
{
    public static function make(): Filter
    {
        return Filter::make('columns')
            ->form([
                ColumnManagerDropdown::make(),
            ])
            ->query(fn (Builder $query): Builder => $query)
            ->indicateUsing(fn (): array => []);
    }
}
