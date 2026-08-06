<?php

declare(strict_types=1);

namespace App\Policies\Organization;

use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectAccess;

class BillingPolicy
{
    public function view(User $user): bool
    {
        return $this->allows($user, 'view_billing');
    }

    public function update(User $user): bool
    {
        return $this->allows($user, 'update_billing');
    }

    private function allows(User $user, string $permission): bool
    {
        $organization = OrganizationService::current();

        return $organization instanceof Organization
            && app(ProjectAccess::class)->organizationAllows($user, $permission, $organization);
    }
}
