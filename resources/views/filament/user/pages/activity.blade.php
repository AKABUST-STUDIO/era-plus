<x-filament-panels::page>
    @php
        $organizations = $this->getOrganizations();
        $projects = $this->getProjects();
    @endphp

    {{ $this->table }}

    @if ($organizations->isNotEmpty() || $projects->isNotEmpty())
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @if ($organizations->isNotEmpty())
                <x-filament::section>
                    <x-slot name="heading">
                        {{ __('user.activity.organizations.heading') }}
                    </x-slot>
                    <x-slot name="description">
                        {{ __('user.activity.organizations.description') }}
                    </x-slot>

                    <ul class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($organizations as $organization)
                            <li class="py-2 flex items-center justify-between gap-3">
                                <div class="text-sm font-medium">{{ $organization->name }}</div>
                                @if ($url = $this->organizationActivityUrl($organization))
                                    <a href="{{ $url }}" class="text-primary-600 hover:underline text-xs">
                                        {{ __('user.activity.organizations.view') }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif

            @if ($projects->isNotEmpty())
                <x-filament::section>
                    <x-slot name="heading">
                        {{ __('user.activity.projects.heading') }}
                    </x-slot>
                    <x-slot name="description">
                        {{ __('user.activity.projects.description') }}
                    </x-slot>

                    <ul class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($projects as $project)
                            <li class="py-2 flex items-center justify-between gap-3">
                                <div class="text-sm">
                                    <div class="font-medium">{{ $project->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $project->organization?->name }}</div>
                                </div>
                                @if ($url = $this->projectActivityUrl($project))
                                    <a href="{{ $url }}" class="text-primary-600 hover:underline text-xs">
                                        {{ __('user.activity.projects.view') }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
