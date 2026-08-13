<?php

declare(strict_types=1);

namespace App\Policies\Organization;

use App\Enums\Permissions\InvoicePermission;
use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectAccess;

class InvoicePolicy
{
    public function view(User $user): bool
    {
        $organization = OrganizationService::current();

        return $organization instanceof Organization
            && app(ProjectAccess::class)->organizationAllows($user, InvoicePermission::View->value, $organization);
    }
}
