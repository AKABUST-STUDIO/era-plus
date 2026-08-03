<?php

namespace App\Filament\Project\Resources\TravelExpenses\Components;

use Filament\Forms\Components\Select;
use Nnjeim\World\Models\Currency;

class CurrencySelect
{
    public static function make(string $name = 'currency'): Select
    {
        return Select::make($name)
            ->hiddenLabel()
            ->validationAttribute(__('finance.fields.currency'))
            ->placeholder(__('finance.fields.currency'))
            ->searchPrompt(__('finance.fields.currency_search_prompt'))
            ->required()
            ->searchable()
            ->default('EUR')
            ->columnSpan(2)
            ->options(fn (): array => ['EUR' => 'EUR — Euro'] + Currency::query()
                ->orderBy('code')
                ->get()
                ->unique('code')
                ->mapWithKeys(fn (Currency $currency): array => [$currency->code => $currency->code.' — '.$currency->name])
                ->all());
    }
}
