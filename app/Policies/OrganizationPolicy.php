<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions\OrganizationPermission;
use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectAccess;

class OrganizationPolicy
{
    public function update(User $user, Organization $organization): bool
    {
        return $this->allows($user, OrganizationPermission::Update->value, $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->allows($user, OrganizationPermission::Delete->value, $organization);
    }

    private function allows(User $user, string $permission, Organization $organization): bool
    {
        return app(ProjectAccess::class)->organizationAllows($user, $permission, $organization);
    }
}
