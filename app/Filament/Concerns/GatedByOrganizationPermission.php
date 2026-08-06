<?php

namespace App\Filament\Concerns;

use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

trait GatedByOrganizationPermission
{
    abstract protected static function organizationPermission(): string;

    public static function canAccess(): bool
    {
        return static::allowsOrganizationPermission(static::organizationPermission());
    }

    protected static function allowsOrganizationPermission(string $permission): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && app(ProjectAccess::class)->currentOrganizationAllows($user, $permission);
    }
}
