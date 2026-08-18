<?php

namespace App\Enums\Subscription;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionTier: string implements HasColor, HasLabel
{
    case Pro = 'pro';

    public function getLabel(): string
    {
        return 'Pro';
    }

    public function getColor(): string
    {
        return 'primary';
    }

    public function stripePriceId(): ?string
    {
        return config('services.stripe.prices.'.$this->value);
    }

    public static function fromStripePriceId(?string $priceId): ?self
    {
        if ($priceId === null) {
            return null;
        }

        foreach (self::cases() as $tier) {
            if ($tier->stripePriceId() === $priceId) {
                return $tier;
            }
        }

        return null;
    }
}
