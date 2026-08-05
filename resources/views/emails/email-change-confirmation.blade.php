<x-mail::message>
# {{ __('emails.email_change_confirm.heading') }}

{{ __('emails.email_change_confirm.intro', ['app' => $appName, 'old' => $oldEmail, 'new' => $newEmail]) }}

<x-mail::button :url="$confirmUrl">
{{ __('emails.email_change_confirm.action') }}
</x-mail::button>

{{ __('emails.email_change_confirm.expiry', ['minutes' => $expiresInMinutes]) }}

{{ __('emails.email_change_confirm.outro') }}<br>
{{ $appName }}
</x-mail::message>
