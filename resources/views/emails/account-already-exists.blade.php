<x-mail::message>
# {{ __('emails.account_already_exists.heading') }}

{{ __('emails.account_already_exists.intro', ['app' => $appName, 'email' => $email]) }}

{{ __('emails.account_already_exists.code') }}

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

{{ __('emails.account_already_exists.alternative') }}

<x-mail::button :url="$magicLinkUrl">
{{ __('emails.account_already_exists.action') }}
</x-mail::button>

{{ __('emails.account_already_exists.expiry', ['minutes' => $expiresInMinutes]) }}

{{ __('emails.account_already_exists.ignore') }}

{{ __('emails.account_already_exists.outro') }}<br>
{{ $appName }}
</x-mail::message>
