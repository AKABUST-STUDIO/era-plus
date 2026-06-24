<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrganizationRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Coordinator = 'coordinator';
    case Leader = 'leader';
    case Member = 'member';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Organization Admin',
            self::Coordinator => 'Coordinator',
            self::Leader => 'Leader',
            self::Member => 'Member',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Coordinator => 'primary',
            self::Leader => 'info',
            self::Member => 'gray',
        };
    }
}
