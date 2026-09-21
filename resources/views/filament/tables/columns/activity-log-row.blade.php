@php
    /** @var \App\Models\ActivityLog $record */
    $record = $getRecord();
    $entry = \App\Presenters\ActivityLogPresenter::present($record, 'date');
@endphp

<x-widgets.activity-row
    :avatarUrl="$entry['avatar_url']"
    :initials="$entry['initials']"
    :description="$entry['description']"
    :when="$entry['when']"
/>
