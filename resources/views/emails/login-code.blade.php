<x-mail::message>
# {{ __('emails.login_code.heading', ['app' => $appName]) }}

{{ __('emails.login_code.intro') }}

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

{{ __('emails.login_code.alternative') }}

<x-mail::button :url="$magicLinkUrl">
{{ __('emails.login_code.action') }}
</x-mail::button>

{{ __('emails.login_code.expiry', ['minutes' => $expiresInMinutes]) }}

{{ __('emails.login_code.outro') }}<br>
{{ $appName }}
</x-mail::message>
