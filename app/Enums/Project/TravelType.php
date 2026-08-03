<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TravelType: string implements HasIcon, HasLabel
{
    case Departure = 'departure';
    case Return = 'return';

    public function getLabel(): string
    {
        return __('finance.travel_types.'.$this->value);
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Departure => 'lucide-plane-takeoff',
            self::Return => 'lucide-plane-landing',
        };
    }
}
