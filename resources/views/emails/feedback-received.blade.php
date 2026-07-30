<x-mail::message>
# {{ __('emails.feedback_received.heading') }}

**{{ __('emails.feedback_received.from') }}:** {{ $feedback->user->name }} ({{ $feedback->user->email }})
**{{ __('emails.feedback_received.subject_label') }}:** {{ $feedback->subject ?? '—' }}
**{{ __('emails.feedback_received.rating') }}:** {{ $feedback->rating ?? '—' }} / 5

> {!! nl2br(e($feedback->description ?? '')) !!}

{{ __('emails.feedback_received.outro') }}<br>
{{ config('app.name') }}
</x-mail::message>
