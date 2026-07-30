<?php

namespace App\Filament\Tables\Filters;

use App\Filament\Forms\Components\FilterGroupDropdown;
use Closure;
use Filament\Schemas\Components\Component;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FilterGroup
{
    /**
     * @param  array<int, Component>  $schema
     */
    public static function make(
        array $schema,
        ?Closure $query = null,
    ): Filter {
        return Filter::make('group')
            ->form([
                FilterGroupDropdown::make()
                    ->icon('lucide-list-filter')
                    ->schema($schema),
            ])
            ->query($query ?? fn (Builder $query): Builder => $query);
    }
}
