<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectRole: string implements HasColor, HasLabel
{
    case Coordinator = 'coordinator';
    case Leader = 'leader';
    case Participant = 'participant';

    public function getLabel(): string
    {
        return match ($this) {
            self::Coordinator => 'Coordinator',
            self::Leader => 'Leader',
            self::Participant => 'Participant',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Coordinator => 'primary',
            self::Leader => 'info',
            self::Participant => 'gray',
        };
    }
}
