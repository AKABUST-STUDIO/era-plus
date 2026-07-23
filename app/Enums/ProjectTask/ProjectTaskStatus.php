<?php

namespace App\Enums\ProjectTask;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectTaskStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Blocked = 'blocked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Blocked => 'Blocked',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'gray',
            self::InProgress => 'info',
            self::Completed => 'success',
            self::Blocked => 'danger',
        };
    }
}
