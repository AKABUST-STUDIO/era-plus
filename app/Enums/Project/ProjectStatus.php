<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Running = 'running';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return __('forms.project.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Running => 'primary',
            self::Closed => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->getLabel();
        }

        return $options;
    }
}
