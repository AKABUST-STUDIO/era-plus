@php
    use App\Enums\Subscription\SubscriptionTier;
    use Illuminate\Support\Js;

    $default = __('organization.register.action');
    $active = ($currentTier ?? null) === SubscriptionTier::Corporate->value
        ? __('organization.register.action_corporate')
        : $default;
@endphp

@capture($label)
    <span x-text="$store.tierPicker?.label ?? {!! Js::from($default) !!}">{{ $active }}</span>
@endcapture

{!! trim($label()) !!}
