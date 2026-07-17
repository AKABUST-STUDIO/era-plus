<x-mail::message>
# {{ __('emails.missing_account.heading') }}

{{ __('emails.missing_account.intro', ['app' => $appName, 'email' => $email]) }}

{{ __('emails.missing_account.create') }}

<x-mail::button :url="$registerUrl">
{{ __('emails.missing_account.action') }}
</x-mail::button>

{{ __('emails.missing_account.ignore') }}

{{ __('emails.missing_account.outro') }}<br>
{{ $appName }}
</x-mail::message>
