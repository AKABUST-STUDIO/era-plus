<x-mail::message>
# We received your support request

**Subject:** {{ $subject }}
**Priority:** {{ $priority }}

> {{ $body }}

We'll get back to you as soon as we can.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
