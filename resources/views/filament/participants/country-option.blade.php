@props(['country'])

<span class="inline-flex items-center gap-1.5">
    <x-filament::icon
        :icon="filled($country->iso2) ? 'flag-4x3-'.strtolower($country->iso2) : 'lucide-flag'"
        class="h-4 w-5"
    />
    {{ $country->name }}
</span>
