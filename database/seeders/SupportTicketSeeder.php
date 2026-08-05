<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SupportTicketSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $openedAt = fn (): Carbon => now()->subDays(random_int(0, 90))->subHours(random_int(0, 23));

        User::query()->each(function (User $user) use ($openedAt): void {
            $organizationId = $user->organizations()->value('organizations.id');
            $projectId = $user->projects()->value('projects.id');

            SupportTicket::factory()->for($user)->count(9)->create([
                'created_at' => $openedAt,
            ]);

            SupportTicket::factory()->for($user)->inProgress()->count(5)->create([
                'created_at' => $openedAt,
                'organization_id' => $organizationId,
            ]);

            SupportTicket::factory()->for($user)->resolved()->count(6)->create([
                'created_at' => $openedAt,
                'organization_id' => $organizationId,
                'project_id' => $projectId,
            ]);

            SupportTicket::factory()->for($user)->closed()->count(4)->create([
                'created_at' => $openedAt,
            ]);
        });
    }
}
