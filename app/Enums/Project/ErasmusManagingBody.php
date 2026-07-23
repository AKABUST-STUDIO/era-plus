<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ErasmusManagingBody: string implements HasColor, HasLabel
{
    case NationalAgency = 'NA';
    case Eacea = 'EACEA';
    case Mixed = 'MIXED';

    public function getLabel(): string
    {
        return __('forms.project.managing_bodies.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NationalAgency => 'info',
            self::Eacea => 'warning',
            self::Mixed => 'gray',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::NationalAgency;
    }

    public function isDeterminate(): bool
    {
        return $this !== self::Mixed;
    }

    /**
     * @return array<string, string>
     */
    public static function assignableOptions(): array
    {
        $options = [];

        foreach (self::cases() as $body) {
            if (! $body->isDeterminate()) {
                continue;
            }

            $options[$body->value] = $body->getLabel();
        }

        return $options;
    }
}
