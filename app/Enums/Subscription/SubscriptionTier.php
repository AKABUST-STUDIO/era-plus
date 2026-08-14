<?php

namespace App\Enums\Subscription;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionTier: string implements HasColor, HasLabel
{
    case Basic = 'basic';
    case Pro = 'pro';
    case Trial = 'trial';
    case Corporate = 'corporate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Pro => 'Pro',
            self::Trial => 'Trial',
            self::Corporate => 'Corporate',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Basic => 'gray',
            self::Pro, self::Trial => 'primary',
            self::Corporate => 'accent',
        };
    }

    public function baseProjectLimit(): ?int
    {
        return match ($this) {
            self::Basic => 1,
            self::Pro, self::Trial, self::Corporate => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public function features(): array
    {
        return __('organization.register.plans.'.$this->value.'.features');
    }

    public function title(): string
    {
        return __('organization.register.plans.'.$this->value.'.title');
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
