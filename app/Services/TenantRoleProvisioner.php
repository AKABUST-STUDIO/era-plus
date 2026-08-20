<?php

namespace App\Services;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleProvisioner
{
    public function ensurePermissions(): void
    {
        $expected = PermissionRegistry::all();
        $existing = Permission::query()->where('guard_name', 'web')->pluck('name')->all();
        $missing = array_diff($expected, $existing);

        if ($missing === []) {
            return;
        }

        foreach ($missing as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function provision(Organization|Project $tenant): void
    {
        $this->ensurePermissions();

        $cases = $tenant instanceof Organization
            ? OrganizationRole::cases()
            : ProjectRole::cases();

        foreach ($cases as $case) {
            /** @var Role $role */
            $role = $tenant->roles()->firstOrCreate(
                ['name' => $case->value, 'guard_name' => 'web'],
                ['label' => $case->getLabel(), 'locked' => $case->isLocked()],
            );

            $role->syncPermissions($case->defaultPermissions());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function reprovisionAll(): void
    {
        $this->ensurePermissions();

        Organization::query()->each(fn (Organization $org) => $this->provision($org));
        Project::query()->each(fn (Project $project) => $this->provision($project));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
