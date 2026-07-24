<?php

namespace App\Console\Commands\Roles;

use App\Services\TenantRoleProvisioner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('roles:reprovision')]
#[Description('Re-sync built-in role permissions on every organization and project against the current PermissionRegistry, then flush spatie/permission caches.')]
class ReprovisionCommand extends Command
{
    public function handle(TenantRoleProvisioner $provisioner): int
    {
        $this->info('Re-provisioning roles on every tenant…');

        $provisioner->reprovisionAll();

        $this->info('Done.');

        return self::SUCCESS;
    }
}
