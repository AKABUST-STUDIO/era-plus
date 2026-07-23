<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ErasmusField: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Youth = 'YOU';
    case SchoolEducation = 'SCH';
    case VocationalEducationAndTraining = 'VET';
    case HigherEducation = 'HED';
    case AdultEducation = 'ADU';
    case Sport = 'SPO';

    public function getLabel(): string
    {
        return __('forms.project.erasmus_fields.'.$this->value);
    }

    public function getDescription(): string
    {
        return __('forms.project.erasmus_field_descriptions.'.$this->value);
    }

    public function getColor(): string
    {
        return $this->isAvailable() ? 'primary' : 'gray';
    }

    public function isAvailable(): bool
    {
        return $this === self::Youth;
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SchoolEducation => 'lucide-backpack',
            self::VocationalEducationAndTraining => 'lucide-wrench',
            self::HigherEducation => 'lucide-graduation-cap',
            self::AdultEducation => 'lucide-book-open',
            self::Youth => 'lucide-users',
            self::Sport => 'lucide-volleyball',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $erasmusField) {
            $options[$erasmusField->value] = $erasmusField->getLabel();
        }

        return $options;
    }

    /**
     * @return array<int, ErasmusKeyAction>
     */
    public function keyActions(): array
    {
        $keyActions = [];

        foreach (ErasmusActionType::cases() as $actionType) {
            if (! in_array($this, $actionType->fields(), true)) {
                continue;
            }

            $keyActions[$actionType->keyAction()->value] = $actionType->keyAction();
        }

        return array_values($keyActions);
    }
}
