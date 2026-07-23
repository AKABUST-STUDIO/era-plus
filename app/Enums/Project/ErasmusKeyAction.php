<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ErasmusKeyAction: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case KeyAction1 = 'KA1';
    case KeyAction2 = 'KA2';
    case KeyAction3 = 'KA3';
    case JeanMonnet = 'JMO';
    case Sport = 'SPT';

    public function getLabel(): string
    {
        return __('forms.project.key_actions.'.$this->value);
    }

    public function getDescription(): string
    {
        return __('forms.project.key_action_descriptions.'.$this->value);
    }

    public function getColor(): string
    {
        return $this->isAvailable() ? 'primary' : 'gray';
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::KeyAction1 => 'lucide-plane-takeoff',
            self::KeyAction2 => 'lucide-handshake',
            self::KeyAction3 => 'lucide-landmark',
            self::JeanMonnet => 'lucide-scale-3d',
            self::Sport => 'lucide-trophy',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::KeyAction1;
    }

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        $descriptions = [];

        foreach (self::cases() as $keyAction) {
            $descriptions[$keyAction->value] = $keyAction->getDescription();
        }

        return $descriptions;
    }

    public function isCrossField(): bool
    {
        return in_array($this, [self::KeyAction3, self::JeanMonnet], true);
    }

    public function managingBody(): ErasmusManagingBody
    {
        return match ($this) {
            self::KeyAction1 => ErasmusManagingBody::NationalAgency,
            self::KeyAction2, self::Sport => ErasmusManagingBody::Mixed,
            self::KeyAction3, self::JeanMonnet => ErasmusManagingBody::Eacea,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForField(?ErasmusField $erasmusField): array
    {
        $keyActions = $erasmusField instanceof ErasmusField ? $erasmusField->keyActions() : self::cases();

        $options = [];

        foreach ($keyActions as $keyAction) {
            $options[$keyAction->value] = $keyAction->getLabel();
        }

        return $options;
    }
}
