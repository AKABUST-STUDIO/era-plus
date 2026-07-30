<x-mail::message>
# {{ __('emails.support_request_received.heading') }}

**{{ __('emails.support_request_received.from') }}:** {{ $request->user->name }} ({{ $request->user->email }})
**{{ __('emails.support_request_received.subject_label') }}:** {{ $request->subject }}

> {!! nl2br(e($request->body)) !!}

{{ __('emails.support_request_received.outro') }}<br>
{{ config('app.name') }}
</x-mail::message>
