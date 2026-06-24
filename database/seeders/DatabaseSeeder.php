<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ShieldPermissionsSeeder::class,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@erasmus.test',
            'password' => 'password',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            // super_admin is a workspace-wide role; Spatie permission with
            // teams enabled writes the team_foreign_key on every pivot row.
            // Resolve the role's own team_id (null for workspace-wide) and
            // push it into the registrar before assigning so the
            // model_has_roles insert doesn't crash on the NOT NULL column.
            $role = \Spatie\Permission\Models\Role::query()
                ->where('name', 'super_admin')
                ->where('guard_name', 'web')
                ->first();

            if ($role !== null) {
                app(PermissionRegistrar::class)->setPermissionsTeamId(
                    $role->getAttribute(config('permission.column_names.team_foreign_key')) ?? 0
                );

                $admin->assignRole($role);
            }
        }
    }
}
