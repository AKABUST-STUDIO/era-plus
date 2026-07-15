<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\TenantRoleProvisioner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(TenantRoleProvisioner::class)->ensurePermissions();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@erasmus.test',
            'password' => 'password',
        ]);
    }
}
