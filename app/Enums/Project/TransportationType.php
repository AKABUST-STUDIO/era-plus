<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TransportationType: string implements HasIcon, HasLabel
{
    case Train = 'train';
    case PublicTransport = 'public_transport';
    case Car = 'car';
    case Flight = 'flight';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('finance.transportation_types.'.$this->value);
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Train => 'lucide-train-front',
            self::PublicTransport => 'lucide-bus',
            self::Car => 'lucide-car',
            self::Flight => 'lucide-plane',
            self::Other => 'lucide-route',
        };
    }
}
