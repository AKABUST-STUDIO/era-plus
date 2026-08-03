<?php

namespace App\Filament\Tables\Filters;

use App\Filament\Forms\Components\CheckToggle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttachmentToggle
{
    public static function make(string $name, string $label): CheckToggle
    {
        return CheckToggle::make($name)
            ->label($label)
            ->live();
    }

    /**
     * @param  Builder<covariant Model>  $query
     * @return Builder<covariant Model>
     */
    public static function apply(Builder $query, string $collection): Builder
    {
        return $query->whereHas('media', fn (Builder $media): Builder => $media->where('collection_name', $collection));
    }
}
