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
    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return [
            ProjectAccess::ABILITY_ADMINISTER_ORGANIZATION,
            ProjectAccess::ABILITY_ADMINISTER_PROJECT,
            ProjectAccess::ABILITY_MANAGE_MEMBERS,
            ProjectAccess::ABILITY_MANAGE_PARTICIPANTS,
            ProjectAccess::ABILITY_MANAGE_SETTINGS,
        ];
    }

    public function ensurePermissions(): void
    {
        foreach (self::permissionNames() as $name) {
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
                ['locked' => true],
            );

            $role->syncPermissions($case->defaultPermissions());
        }
    }
}
