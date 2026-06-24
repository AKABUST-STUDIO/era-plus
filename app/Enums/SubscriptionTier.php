<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionTier: string implements HasColor, HasLabel
{
    case Free = 'free';
    case Pro = 'pro';
    case Premium = 'premium';

    public function getLabel(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Pro => 'Pro',
            self::Premium => 'Premium',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Free => 'gray',
            self::Pro => 'primary',
            self::Premium => 'warning',
        };
    }

    public function baseProjectLimit(): ?int
    {
        return match ($this) {
            self::Free => 1,
            self::Pro, self::Premium => null,
        };
    }
}
