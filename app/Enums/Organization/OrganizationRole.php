<?php

namespace App\Enums\Organization;

use App\Services\ProjectAccess;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrganizationRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Member = 'member';

    public function getLabel(): string
    {
        return __('organization.roles.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Member => 'gray',
        };
    }

    /**
     * @return list<string>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => [ProjectAccess::ABILITY_ADMINISTER_ORGANIZATION],
            self::Member => [],
        };
    }
}
