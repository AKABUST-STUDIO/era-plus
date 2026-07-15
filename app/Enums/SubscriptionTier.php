<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionTier: string implements HasColor, HasLabel
{
    case Basic = 'basic';
    case Pro = 'pro';
    case Trial = 'trial';

    public function getLabel(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Pro => 'Pro',
            self::Trial => 'Trial',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Basic => 'gray',
            self::Pro, self::Trial => 'primary',
        };
    }

    public function baseProjectLimit(): ?int
    {
        return match ($this) {
            self::Basic => 1,
            self::Pro, self::Trial => null,
        };
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
