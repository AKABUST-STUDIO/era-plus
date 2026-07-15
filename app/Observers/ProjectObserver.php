<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\TenantRoleProvisioner;

class ProjectObserver
{
    public function created(Project $project): void
    {
        app(TenantRoleProvisioner::class)->provision($project);
    }
}
