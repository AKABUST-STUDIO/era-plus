@props(['country'])

<span class="participant-country-option">
    <x-filament::icon
        :icon="filled($country->iso2) ? 'flag-4x3-'.strtolower($country->iso2) : 'lucide-flag'"
        class="participant-country-option__icon"
    />
    {{ $country->name }}
</span>
