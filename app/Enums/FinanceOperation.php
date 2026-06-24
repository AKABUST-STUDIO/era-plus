<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinanceOperation: string implements HasColor, HasLabel
{
    case Add = 'add';
    case Subtract = 'subtract';

    public function getLabel(): string
    {
        return match ($this) {
            self::Add => 'Add',
            self::Subtract => 'Subtract',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Add => 'success',
            self::Subtract => 'danger',
        };
    }

    public function signedMultiplier(): int
    {
        return $this === self::Add ? 1 : -1;
    }
}
