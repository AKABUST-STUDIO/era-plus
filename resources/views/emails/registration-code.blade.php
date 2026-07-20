<x-mail::message>
# {{ __('emails.registration_code.heading') }}

{{ __('emails.registration_code.intro', ['app' => $appName, 'email' => $email]) }}

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

{{ __('emails.registration_code.alternative') }}

<x-mail::button :url="$magicLinkUrl">
{{ __('emails.registration_code.action') }}
</x-mail::button>

{{ __('emails.registration_code.expiry', ['minutes' => $expiresInMinutes]) }}

{{ __('emails.registration_code.outro') }}<br>
{{ $appName }}
</x-mail::message>
