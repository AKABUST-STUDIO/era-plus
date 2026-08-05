<?php

namespace App\Enums\SupportTicket;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupportTicketStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::InProgress => 'warning',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };
    }
}
