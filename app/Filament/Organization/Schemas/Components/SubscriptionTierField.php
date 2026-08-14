<?php

namespace App\Filament\Organization\Schemas\Components;

use App\Enums\Subscription\SubscriptionTier;
use Filament\Forms\Components\ViewField;

class SubscriptionTierField extends ViewField
{
    protected string $view = 'filament.forms.components.subscription-tier-picker';

    /**
     * @return array<string, string>
     */
    public function features(): array
    {
        return [
            SubscriptionTier::Basic->value => SubscriptionTier::Basic->features(),
            SubscriptionTier::Pro->value => SubscriptionTier::Pro->features(),
            SubscriptionTier::Corporate->value => SubscriptionTier::Corporate->features(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function submitLabels(): array
    {
        return [
            SubscriptionTier::Basic->value => __('organization.register.action'),
            SubscriptionTier::Pro->value => __('organization.register.action'),
            SubscriptionTier::Corporate->value => __('organization.register.action_corporate'),
        ];
    }

    public function defaultSubmitLabel(): string
    {
        return $this->submitLabels()[$this->getState()] ?? __('organization.register.action');
    }

    public function basicValue(): string
    {
        return SubscriptionTier::Basic->value;
    }

    public function proValue(): string
    {
        return SubscriptionTier::Pro->value;
    }

    public function corporateValue(): string
    {
        return SubscriptionTier::Corporate->value;
    }
}
