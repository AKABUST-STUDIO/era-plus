@props(['record'])

@php
    $isRegistered = $record instanceof \App\Models\Organization;
    $customAvatar = $isRegistered && method_exists($record, 'getFilamentAvatarUrl')
        ? $record->getFilamentAvatarUrl()
        : null;
    $avatarUrl = $customAvatar
        ?: 'https://ui-avatars.com/api/?name='.urlencode($record->name ?? '?').'&size=128';
@endphp

<div class="participant-select-option">
    <img
        src="{{ $avatarUrl }}"
        alt=""
        class="participant-select-option__avatar"
    />
    <div class="participant-select-option__body">
        @if ($isRegistered)
            <div class="participant-select-option__meta">
                <span class="participant-select-option__badge participant-select-option__badge--verified">
                    <x-filament::icon icon="lucide-badge-check" class="participant-select-option__icon" />
                    {{ __('participant.select_option.verified') }}
                </span>
            </div>
        @endif

        <span class="participant-select-option__name">{{ $record->name }}</span>
    </div>
</div>
