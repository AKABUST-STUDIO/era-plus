<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectAccess;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $this->allows($user, 'view_organization', $organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->allows($user, 'update_organization', $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->allows($user, 'delete_organization', $organization);
    }

    private function allows(User $user, string $permission, Organization $organization): bool
    {
        return app(ProjectAccess::class)->organizationAllows($user, $permission, $organization);
    }
}
