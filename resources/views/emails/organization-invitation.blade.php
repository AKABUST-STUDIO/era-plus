<x-mail::message>
# {{ __('emails.organization_invitation.heading', ['organization' => $organizationName]) }}

{!! __('emails.organization_invitation.intro', ['inviter' => $inviterName, 'organization' => $organizationName, 'role' => $roleLabel]) !!}

{{ __('emails.organization_invitation.sign_in_intro') }}

<x-mail::button :url="$magicLinkUrl">
{{ __('emails.organization_invitation.sign_in_action', ['organization' => $organizationName]) }}
</x-mail::button>

{{ __('emails.organization_invitation.expiry', ['minutes' => $expiresInMinutes]) }}

{{ __('emails.organization_invitation.ignore') }}

{{ __('emails.organization_invitation.outro') }}<br>
{{ config('app.name') }}
</x-mail::message>
