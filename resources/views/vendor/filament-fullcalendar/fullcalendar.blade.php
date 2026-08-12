@php
    $plugin = \Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::get();

    $config = $this->getConfig();
    $config['datesSet'] = '__DATES_SET__';
    $configJson = str_replace(
        '"__DATES_SET__"',
        '(info) => Livewire.dispatch("calendar-title-changed", { title: info.view.title })',
        json_encode($config, JSON_UNESCAPED_SLASHES),
    );
@endphp

<div>
    <div wire:ignore x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-fullcalendar-alpine', 'saade/filament-fullcalendar') }}"
        x-ignore x-data="fullcalendar({
            locale: @js($plugin->getLocale()),
            plugins: @js($plugin->getPlugins()),
            schedulerLicenseKey: @js($plugin->getSchedulerLicenseKey()),
            timeZone: @js($plugin->getTimezone()),
            config: {{ $configJson }},
            editable: @json($plugin->isEditable()),
            selectable: @json($plugin->isSelectable()),
            eventClassNames: {!! htmlspecialchars($this->eventClassNames(), ENT_COMPAT) !!},
            eventContent: {!! htmlspecialchars($this->eventContent(), ENT_COMPAT) !!},
            eventDidMount: {!! htmlspecialchars($this->eventDidMount(), ENT_COMPAT) !!},
            eventWillUnmount: {!! htmlspecialchars($this->eventWillUnmount(), ENT_COMPAT) !!},
        })" class="filament-fullcalendar"></div>

    <x-filament-actions::modals />
</div>
