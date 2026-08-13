<?php

declare(strict_types=1);

namespace App\Policies\Organization;

use App\Enums\Permissions\BillingPermission;
use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectAccess;

class BillingPolicy
{
    public function view(User $user): bool
    {
        return $this->allows($user, BillingPermission::View->value);
    }

    public function update(User $user): bool
    {
        return $this->allows($user, BillingPermission::Update->value);
    }

    private function allows(User $user, string $permission): bool
    {
        $organization = OrganizationService::current();

        return $organization instanceof Organization
            && app(ProjectAccess::class)->organizationAllows($user, $permission, $organization);
    }
}
