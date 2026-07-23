<?php

namespace App\Enums\Project;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ErasmusActionType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Ka120 = 'KA120';
    case Ka121 = 'KA121';
    case Ka122 = 'KA122';
    case Ka131 = 'KA131';
    case Ka171 = 'KA171';
    case Ka151 = 'KA151';
    case Ka152 = 'KA152';
    case Ka153 = 'KA153';
    case Ka154 = 'KA154';
    case Ka155 = 'KA155';
    case Ka210 = 'KA210';
    case Ka220 = 'KA220';
    case Ka226 = 'KA226';
    case ErasmusMundusJointMasters = 'EMJM';
    case ErasmusMundusDesignMeasures = 'EMDM';
    case AlliancesForInnovation = 'ALL';
    case TeacherAcademies = 'TA';
    case CapacityBuildingHigherEducation = 'CBHE';
    case CapacityBuildingVet = 'CBVET';
    case CapacityBuildingYouth = 'CBY';
    case EuropeanYouthTogether = 'EYT';
    case JeanMonnetModule = 'JMO-MOD';
    case JeanMonnetChair = 'JMO-CHR';
    case JeanMonnetCentreOfExcellence = 'JMO-COE';
    case JeanMonnetNetworks = 'JMO-NET';
    case JeanMonnetTeacherTraining = 'JMO-TT';
    case SportCooperationPartnerships = 'SPT-COOP';
    case SportSmallScalePartnerships = 'SPT-SMALL';
    case SportEvents = 'SPT-EVENT';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function keyAction(): ErasmusKeyAction
    {
        return match ($this) {
            self::Ka120, self::Ka121, self::Ka122, self::Ka131, self::Ka171,
            self::Ka151, self::Ka152, self::Ka153, self::Ka154, self::Ka155 => ErasmusKeyAction::KeyAction1,
            self::Ka210, self::Ka220, self::Ka226, self::ErasmusMundusJointMasters,
            self::ErasmusMundusDesignMeasures, self::AlliancesForInnovation, self::TeacherAcademies,
            self::CapacityBuildingHigherEducation, self::CapacityBuildingVet,
            self::CapacityBuildingYouth => ErasmusKeyAction::KeyAction2,
            self::EuropeanYouthTogether => ErasmusKeyAction::KeyAction3,
            self::JeanMonnetModule, self::JeanMonnetChair, self::JeanMonnetCentreOfExcellence,
            self::JeanMonnetNetworks, self::JeanMonnetTeacherTraining => ErasmusKeyAction::JeanMonnet,
            self::SportCooperationPartnerships, self::SportSmallScalePartnerships,
            self::SportEvents => ErasmusKeyAction::Sport,
        };
    }

    /**
     * @return array<int, ErasmusField>
     */
    public function fields(): array
    {
        return match ($this) {
            self::Ka120, self::Ka121, self::Ka210 => [
                ErasmusField::SchoolEducation,
                ErasmusField::VocationalEducationAndTraining,
                ErasmusField::AdultEducation,
                ErasmusField::Youth,
            ],
            self::Ka122 => [
                ErasmusField::SchoolEducation,
                ErasmusField::VocationalEducationAndTraining,
                ErasmusField::AdultEducation,
            ],
            self::Ka131, self::Ka171, self::ErasmusMundusJointMasters,
            self::ErasmusMundusDesignMeasures, self::CapacityBuildingHigherEducation,
            self::JeanMonnetModule, self::JeanMonnetChair, self::JeanMonnetCentreOfExcellence,
            self::JeanMonnetNetworks => [ErasmusField::HigherEducation],
            self::Ka151, self::Ka152, self::Ka153, self::Ka154, self::Ka155,
            self::CapacityBuildingYouth, self::EuropeanYouthTogether => [ErasmusField::Youth],
            self::Ka220 => [
                ErasmusField::SchoolEducation,
                ErasmusField::VocationalEducationAndTraining,
                ErasmusField::AdultEducation,
                ErasmusField::HigherEducation,
                ErasmusField::Youth,
            ],
            self::Ka226, self::CapacityBuildingVet => [ErasmusField::VocationalEducationAndTraining],
            self::AlliancesForInnovation => [
                ErasmusField::HigherEducation,
                ErasmusField::VocationalEducationAndTraining,
            ],
            self::TeacherAcademies => [ErasmusField::SchoolEducation],
            self::JeanMonnetTeacherTraining => [
                ErasmusField::SchoolEducation,
                ErasmusField::VocationalEducationAndTraining,
            ],
            self::SportCooperationPartnerships, self::SportSmallScalePartnerships,
            self::SportEvents => [ErasmusField::Sport],
        };
    }

    public function managingBody(): ErasmusManagingBody
    {
        return match ($this) {
            self::Ka120, self::Ka121, self::Ka122, self::Ka131, self::Ka171,
            self::Ka151, self::Ka152, self::Ka153, self::Ka154, self::Ka155,
            self::Ka210, self::Ka220, self::SportSmallScalePartnerships => ErasmusManagingBody::NationalAgency,
            default => ErasmusManagingBody::Eacea,
        };
    }

    public function isAvailable(): bool
    {
        return match ($this) {
            self::Ka151, self::Ka152, self::Ka153, self::Ka154, self::Ka155 => true,
            default => false,
        };
    }

    public function getDescription(): string
    {
        return __('forms.project.action_types.'.$this->value);
    }

    public function getColor(): string
    {
        return $this->isAvailable() ? 'primary' : 'gray';
    }

    public function getIcon(): ?string
    {
        return $this->keyAction()->getIcon();
    }

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        $descriptions = [];

        foreach (self::cases() as $actionType) {
            $descriptions[$actionType->value] = $actionType->getDescription();
        }

        return $descriptions;
    }

    public function isCrossField(): bool
    {
        return $this->keyAction()->isCrossField();
    }

    /**
     * @return array{min: int, max: int}
     */
    public function durationRange(): array
    {
        return match ($this) {
            self::Ka122 => ['min' => 6, 'max' => 18],
            self::Ka121, self::Ka151 => ['min' => 12, 'max' => 24],
            self::Ka131 => ['min' => 24, 'max' => 26],
            self::Ka171, self::AlliancesForInnovation, self::CapacityBuildingHigherEducation,
            self::EuropeanYouthTogether => ['min' => 24, 'max' => 36],
            self::Ka152, self::Ka153, self::Ka154, self::Ka155 => ['min' => 3, 'max' => 24],
            self::Ka210 => ['min' => 6, 'max' => 24],
            self::Ka220, self::CapacityBuildingVet, self::SportCooperationPartnerships => ['min' => 12, 'max' => 36],
            self::CapacityBuildingYouth, self::SportSmallScalePartnerships => ['min' => 12, 'max' => 24],
            self::Ka226 => ['min' => 48, 'max' => 48],
            self::ErasmusMundusJointMasters => ['min' => 72, 'max' => 78],
            self::ErasmusMundusDesignMeasures => ['min' => 15, 'max' => 15],
            self::TeacherAcademies, self::JeanMonnetModule, self::JeanMonnetChair,
            self::JeanMonnetCentreOfExcellence, self::JeanMonnetNetworks,
            self::JeanMonnetTeacherTraining => ['min' => 36, 'max' => 36],
            self::SportEvents => ['min' => 12, 'max' => 18],
            self::Ka120 => ['min' => 1, 'max' => 120],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function optionsFor(?ErasmusField $erasmusField, ?ErasmusKeyAction $keyAction): array
    {
        $options = [];

        foreach (self::cases() as $actionType) {
            if ($erasmusField instanceof ErasmusField && ! in_array($erasmusField, $actionType->fields(), true)) {
                continue;
            }

            if ($keyAction instanceof ErasmusKeyAction && $actionType->keyAction() !== $keyAction) {
                continue;
            }

            $options[$actionType->value] = $actionType->getLabel();
        }

        return $options;
    }
}
