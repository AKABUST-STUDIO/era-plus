<?php

namespace App\Enums\Project;

use App\Enums\Permissions\ProjectEventPermission;
use App\Enums\Permissions\ProjectUserPermission;
use App\Services\PermissionRegistry;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Participant = 'participant';

    public function getLabel(): string
    {
        return __('forms.project.roles.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Participant => 'gray',
        };
    }

    public function isLocked(): bool
    {
        return match ($this) {
            self::Admin => true,
            self::Participant => false,
        };
    }

    /**
     * @return list<string>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => PermissionRegistry::granular(PermissionRegistry::SCOPE_PROJECT),
            self::Participant => [
                ProjectUserPermission::ViewAny->value,
                ProjectEventPermission::ViewAny->value,
            ],
        };
    }
}
