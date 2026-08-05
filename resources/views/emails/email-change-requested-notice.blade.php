<x-mail::message>
# {{ __('emails.email_change_notice.heading') }}

{{ __('emails.email_change_notice.intro', ['app' => $appName, 'new' => $newEmail]) }}

{{ __('emails.email_change_notice.body') }}

{{ __('emails.email_change_notice.action_intro') }}

<x-mail::button :url="$securityUrl">
{{ __('emails.email_change_notice.action') }}
</x-mail::button>

{{ __('emails.email_change_notice.outro') }}<br>
{{ $appName }}
</x-mail::message>
