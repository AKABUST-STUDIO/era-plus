<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions\RolePermission;
use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, RolePermission::ViewAny->value, $this->activeTenant());
    }

    public function view(User $user, Role $role): bool
    {
        return $this->allows($user, RolePermission::View->value, $role->roleable);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, RolePermission::Create->value, $this->activeTenant());
    }

    public function update(User $user, Role $role): bool
    {
        return ! $role->locked && $this->allows($user, RolePermission::UpdateAny->value, $role->roleable);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, RolePermission::UpdateAny->value, $this->activeTenant());
    }

    public function delete(User $user, Role $role): bool
    {
        return ! $role->locked && $this->allows($user, RolePermission::DeleteAny->value, $role->roleable);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, RolePermission::DeleteAny->value, $this->activeTenant());
    }

    private function allows(User $user, string $permission, ?object $tenant): bool
    {
        $access = app(ProjectAccess::class);

        return match (true) {
            $tenant instanceof Organization => $access->organizationAllows($user, $permission, $tenant),
            $tenant instanceof Project => $access->administersProject($user, $tenant),
            default => false,
        };
    }

    private function activeTenant(): Organization|Project|null
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Organization || $tenant instanceof Project) {
            return $tenant;
        }

        return OrganizationService::current();
    }
}
