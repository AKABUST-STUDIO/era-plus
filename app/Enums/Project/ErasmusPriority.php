<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasLabel;

enum ErasmusPriority: string implements HasLabel
{
    case InclusionAndDiversity = 'inclusion_and_diversity';
    case DigitalTransformation = 'digital_transformation';
    case EnvironmentAndClimate = 'environment_and_climate';
    case DemocraticLife = 'democratic_life';

    public function getLabel(): string
    {
        return __('forms.project.priorities.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $priority) {
            $options[$priority->value] = $priority->getLabel();
        }

        return $options;
    }
}
