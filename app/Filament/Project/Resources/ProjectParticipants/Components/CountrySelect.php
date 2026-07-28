<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Components;

use Filament\Forms\Components\Select;
use Nnjeim\World\Models\Country;

class CountrySelect
{
    public static function make(string $name = 'country_id'): Select
    {
        $searchFn = fn (?string $search): array => Country::query()
            ->when(filled($search), fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Country $country): array => [$country->id => view('filament.participants.country-option', ['country' => $country])->render()])
            ->all();

        return Select::make($name)
            ->hiddenLabel()
            ->validationAttribute(__('participant.fields.country'))
            ->placeholder(__('participant.fields.country'))
            ->searchPrompt(__('participant.fields.country_search_prompt'))
            ->required()
            ->searchable()
            ->preload()
            ->allowHtml()
            ->columnSpan(2)
            ->options(fn (): array => $searchFn(null))
            ->getSearchResultsUsing($searchFn)
            ->getOptionLabelUsing(fn ($value): ?string => filled($country = Country::find($value))
                ? view('filament.participants.country-option', ['country' => $country])->render()
                : null);
    }
}
