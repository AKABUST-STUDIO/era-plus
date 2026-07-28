@props(['record'])

@php
    $isUser = $record instanceof \App\Models\User;
    $verified = $isUser && $record->email_verified_at !== null;
@endphp

<div class="participant-select-option">
    <img
        src="{{ $record->avatarUrl() }}"
        alt=""
        class="participant-select-option__avatar"
    />
    <div class="participant-select-option__body">
        @if ($verified)
            <span class="participant-select-option__badge participant-select-option__badge--verified">
                <x-filament::icon icon="lucide-badge-check" class="participant-select-option__icon" />
                {{ __('participant.select_option.verified') }}
            </span>
        @endif
        <span class="participant-select-option__name">{{ $record->name }}</span>

        @if (filled($record->email))
            <span class="participant-select-option__email">
                <x-filament::icon icon="lucide-mail" class="participant-select-option__icon" />
                {{ $record->email }}
            </span>
        @endif
    </div>
</div>
