<x-mail::message>
# {{ __('emails.welcome.heading', ['app' => $appName]) }}

{{ __('emails.welcome.intro', ['name' => $name]) }}

{{ __('emails.welcome.body', ['app' => $appName]) }}

<x-mail::button :url="$dashboardUrl">
{{ __('emails.welcome.action', ['app' => $appName]) }}
</x-mail::button>

{{ __('emails.welcome.help') }}

{{ __('emails.welcome.outro') }}<br>
{{ $appName }}
</x-mail::message>
