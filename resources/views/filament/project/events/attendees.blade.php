@php
    $attendees = $getRecord()->attendees()->with('projectParticipant.participable')->get();
@endphp

@if ($attendees->isEmpty())
    <div class="text-sm text-gray-500 dark:text-gray-400">—</div>
@else
    <ul class="flex flex-col gap-2">
        @foreach ($attendees as $attendee)
            @php
                $person = $attendee->projectParticipant?->participable;
                if (! $person) { continue; }
                $name = (string) $person->name;
                $email = (string) $person->email;
                $avatar = $attendee->projectParticipant->avatarUrl();
                $status = $attendee->response_status instanceof \App\Enums\Project\AttendeeResponseStatus
                    ? $attendee->response_status
                    : \App\Enums\Project\AttendeeResponseStatus::tryFrom((string) $attendee->response_status);
            @endphp

            <li class="flex items-center gap-3">
                <x-filament::avatar :src="$avatar" :alt="$name" size="md" class="shrink-0" />

                <div class="flex flex-col min-w-0 flex-1">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-sm font-medium text-gray-950 dark:text-white truncate">{{ $name }}</span>
                        @if ($status)
                            <x-filament::badge :color="$status->getColor()" size="xs">
                                {{ $status->getLabel() }}
                            </x-filament::badge>
                        @endif
                    </div>
                    @if ($email)
                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $email }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
