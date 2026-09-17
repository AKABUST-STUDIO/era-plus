<?php

namespace App\Enums\Project;

use App\Enums\Permissions\ActivityPermission;
use App\Enums\Permissions\ParticipantPermission;
use App\Enums\Permissions\ProjectEventPermission;
use App\Enums\Permissions\ProjectPermission;
use App\Enums\Permissions\ProjectUserPermission;
use App\Enums\Permissions\RolePermission;
use App\Enums\Permissions\TravelExpensePermission;
use App\Services\PermissionRegistry;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Member = 'member';
    case Participant = 'participant';

    public function getLabel(): string
    {
        return __('forms.project.roles.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Member => 'info',
            self::Participant => 'gray',
        };
    }

    public function isLocked(): bool
    {
        return match ($this) {
            self::Admin, self::Member, self::Participant => true,
        };
    }

    /**
     * @return list<string>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => PermissionRegistry::granular(PermissionRegistry::SCOPE_PROJECT),
            self::Member => [
                RolePermission::ViewAny->value,
                RolePermission::View->value,
                ProjectPermission::Update->value,
                ProjectEventPermission::ViewAny->value,
                ProjectEventPermission::View->value,
                ProjectEventPermission::Create->value,
                ProjectEventPermission::UpdateAny->value,
                ProjectEventPermission::DeleteAny->value,
                TravelExpensePermission::ViewAny->value,
                TravelExpensePermission::View->value,
                TravelExpensePermission::Create->value,
                TravelExpensePermission::Update->value,
                TravelExpensePermission::UpdateAny->value,
                TravelExpensePermission::Delete->value,
                TravelExpensePermission::DeleteAny->value,
                TravelExpensePermission::Import->value,
                TravelExpensePermission::Export->value,
                TravelExpensePermission::CountryLimits->value,
                ParticipantPermission::ViewAny->value,
                ParticipantPermission::View->value,
                ParticipantPermission::Create->value,
                ParticipantPermission::Update->value,
                ParticipantPermission::UpdateAny->value,
                ParticipantPermission::Delete->value,
                ParticipantPermission::DeleteAny->value,
                ParticipantPermission::Import->value,
                ParticipantPermission::Export->value,
                ActivityPermission::View->value,
                ProjectUserPermission::ViewAny->value,
                ProjectUserPermission::View->value,
            ],
            self::Participant => [
                TravelExpensePermission::ViewAny->value,
                TravelExpensePermission::View->value,
                TravelExpensePermission::Create->value,
                TravelExpensePermission::Update->value,
                TravelExpensePermission::Delete->value,
                TravelExpensePermission::Import->value,
                ParticipantPermission::ViewAny->value,
                ParticipantPermission::View->value,
                ParticipantPermission::Update->value,
                ProjectEventPermission::ViewAny->value,
                TravelExpensePermission::ViewAny->value,
                TravelExpensePermission::Create->value,
                TravelExpensePermission::Update->value,
                TravelExpensePermission::Delete->value,
            ],
        };
    }
}
