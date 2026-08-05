<x-mail::message>
# {{ __('emails.support_ticket_received.heading') }}

**{{ __('emails.support_ticket_received.from') }}:** {{ $ticket->user->name }} ({{ $ticket->user->email }})
**{{ __('emails.support_ticket_received.subject_label') }}:** {{ $ticket->subject }}

> {!! nl2br(e($ticket->body)) !!}

{{ __('emails.support_ticket_received.outro') }}<br>
{{ config('app.name') }}
</x-mail::message>
