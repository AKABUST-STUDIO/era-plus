@props(['url', 'name'])

<span class="fc-avatar" title="{{ $name }}">
    <x-filament::avatar :src="$url" :alt="$name" size="xs" />
</span>
