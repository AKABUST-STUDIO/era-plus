<x-mail::message>
# You've been added to {{ $organizationName }}

{{ $inviterName }} added you to **{{ $organizationName }}** as **{{ $roleLabel }}**.

Set your password to sign in for the first time:

<x-mail::button :url="$resetUrl">
Set your password
</x-mail::button>

If you weren't expecting this, you can safely ignore the email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
