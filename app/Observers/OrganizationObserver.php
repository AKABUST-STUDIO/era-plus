<?php

namespace App\Observers;

use App\Models\Organization;
use App\Services\TenantRoleProvisioner;

class OrganizationObserver
{
    public function created(Organization $organization): void
    {
        app(TenantRoleProvisioner::class)->provision($organization);
    }
}
