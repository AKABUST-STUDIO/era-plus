<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldPermissionsSeeder extends Seeder
{
    /**
     * Seed permissions matching the Shield-generated policy names plus the
     * default Organization roles. Idempotent — safe to re-run after
     * shield:generate adds new entities.
     */
    public function run(): void
    {
        $entities = [
            'FinanceEntry',
            'Participant',
            'Project',
            'ProjectEvent',
            'ProjectMember',
            'ProjectTask',
            'TravelExpense',
        ];

        $abilities = [
            'ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
            'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny',
            'Replicate', 'Reorder',
        ];

        // Without tenant scope so super-admin can manage globally.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($entities as $entity) {
            foreach ($abilities as $ability) {
                Permission::firstOrCreate([
                    'name' => $ability.':'.$entity,
                    'guard_name' => 'web',
                ]);
            }
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::query()->get());

        // Coordinator role per Spatie team scope — owners must seed
        // a Coordinator role per organization to grant per-tenant rights.
        // We define the role names here; the per-org tenant_id is set when
        // assigning to users.
        foreach (OrganizationRole::cases() as $role) {
            Role::firstOrCreate(['name' => $role->getLabel(), 'guard_name' => 'web']);
        }
    }
}
