<?php

declare(strict_types=1);

namespace App\Policies;

use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectAccess;

class ActivityLogPolicy
{
    public function view(User $user): bool
    {
        $organization = OrganizationService::current();

        return $organization instanceof Organization
            && app(ProjectAccess::class)->organizationAllows($user, 'view_activity', $organization);
    }
}
