<x-mail::message>
# Sign in to {{ config('app.name') }}

Use this one-time code to sign in:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

Or click the button below to sign in directly:

<x-mail::button :url="$magicLinkUrl">
Sign in
</x-mail::button>

The code and link expire in {{ $expiresInMinutes }} minutes. If you didn't request this, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
