<?php

namespace App\Enums\FinanceEntry;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BudgetCategory: string implements HasColor, HasLabel
{
    case OrganizationalSupport = 'organizational_support';
    case Travel = 'travel';
    case IndividualSupport = 'individual_support';
    case LinguisticSupport = 'linguistic_support';
    case CourseFees = 'course_fees';
    case InclusionSupport = 'inclusion_support';
    case SpecialNeedsSupport = 'special_needs_support';
    case ExceptionalCosts = 'exceptional_costs';

    public function getLabel(): string
    {
        return match ($this) {
            self::OrganizationalSupport => 'Organizational support',
            self::Travel => 'Travel',
            self::IndividualSupport => 'Individual support',
            self::LinguisticSupport => 'Linguistic support',
            self::CourseFees => 'Course / training fees',
            self::InclusionSupport => 'Inclusion support',
            self::SpecialNeedsSupport => 'Special needs support',
            self::ExceptionalCosts => 'Exceptional costs',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OrganizationalSupport => 'info',
            self::Travel => 'primary',
            self::IndividualSupport => 'success',
            self::LinguisticSupport => 'warning',
            self::CourseFees => 'warning',
            self::InclusionSupport => 'success',
            self::SpecialNeedsSupport => 'success',
            self::ExceptionalCosts => 'danger',
        };
    }
}
