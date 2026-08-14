<x-mail::message>
# {{ __('emails.corporate_enquiry_received.heading') }}

**{{ __('emails.corporate_enquiry_received.from') }}:** {{ $user->name }} ({{ $user->email }})
**{{ __('emails.corporate_enquiry_received.subject_label') }}:** {{ $subject }}

> {!! nl2br(e($body)) !!}

{{ __('emails.corporate_enquiry_received.outro') }}<br>
{{ config('app.name') }}
</x-mail::message>
