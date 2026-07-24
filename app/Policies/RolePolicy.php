<?php

declare(strict_types=1);

namespace App\Policies;

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
        return $this->isTenantAdmin($user, $this->activeTenant());
    }

    public function view(User $user, Role $role): bool
    {
        return $this->isTenantAdmin($user, $role->roleable);
    }

    public function create(User $user): bool
    {
        return $this->isTenantAdmin($user, $this->activeTenant());
    }

    public function update(User $user, Role $role): bool
    {
        return $this->isTenantAdmin($user, $role->roleable) && ! $role->locked;
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->isTenantAdmin($user, $role->roleable) && ! $role->locked;
    }

    private function isTenantAdmin(User $user, ?object $tenant): bool
    {
        $access = app(ProjectAccess::class);

        return match (true) {
            $tenant instanceof Organization => $access->administersOrganization($user, $tenant),
            $tenant instanceof Project => $access->administersProject($user, $tenant),
            default => false,
        };
    }

    private function activeTenant(): Organization|Project|null
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization || $tenant instanceof Project ? $tenant : null;
    }
}
