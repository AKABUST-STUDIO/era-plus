<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttendeeResponseStatus: string implements HasColor, HasLabel
{
    case NeedsAction = 'needsAction';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Tentative = 'tentative';

    public function getLabel(): string
    {
        return __('events.response_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NeedsAction => 'gray',
            self::Accepted => 'success',
            self::Declined => 'danger',
            self::Tentative => 'warning',
        };
    }

    public static function fromGoogle(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::NeedsAction;
    }
}
