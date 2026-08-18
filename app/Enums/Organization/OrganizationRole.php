<?php

namespace App\Enums\Organization;

use App\Enums\Permissions\OrganizationUserPermission;
use App\Enums\Permissions\ProjectPermission;
use App\Services\PermissionRegistry;
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
        return 'gray';
    }

    public function isLocked(): bool
    {
        return match ($this) {
            self::Admin => true,
            self::Member => false,
        };
    }

    /**
     * @return list<string>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => PermissionRegistry::granular(PermissionRegistry::SCOPE_ORGANIZATION),
            self::Member => [
                ProjectPermission::ViewAny->value,
                OrganizationUserPermission::View->value,
            ],
        };
    }
}
