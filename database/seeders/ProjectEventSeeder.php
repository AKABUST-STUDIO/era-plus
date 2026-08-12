<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Project\ProjectEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProjectEventSeeder extends Seeder
{
    public function run(): void
    {
        Project::query()->each(function (Project $project): void {
            $now = Carbon::now();
            $startOfWeek = $now->copy()->startOfWeek();

            $samples = [
                ['Kickoff meeting', 'Madrid HQ', $startOfWeek->copy()->addDays(0)->setTime(9, 0), 90],
                ['Orientation session', 'Room A', $startOfWeek->copy()->addDays(1)->setTime(11, 0), 60],
                ['Field trip', 'City center', $startOfWeek->copy()->addDays(2)->setTime(10, 0), 240],
                ['Language workshop', 'Room B', $startOfWeek->copy()->addDays(3)->setTime(14, 30), 90],
                ['Team dinner', 'Trattoria da Luigi', $startOfWeek->copy()->addDays(4)->setTime(19, 0), 120],
                ['Cultural excursion', 'Museum district', $startOfWeek->copy()->addDays(5)->setTime(10, 0), 180],
                ['Weekly retrospective', 'Room A', $startOfWeek->copy()->addDays(7)->setTime(16, 0), 60],
                ['Skills workshop', 'Room C', $startOfWeek->copy()->addDays(8)->setTime(9, 30), 120],
                ['Guest lecture', 'Auditorium', $startOfWeek->copy()->addDays(9)->setTime(15, 0), 90],
                ['Farewell brunch', 'Café Central', $startOfWeek->copy()->addDays(11)->setTime(11, 0), 120],
            ];

            foreach ($samples as [$title, $location, $startsAt, $minutes]) {
                ProjectEvent::withoutGlobalScopes()->updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'title' => $title,
                        'starts_at' => $startsAt,
                    ],
                    [
                        'description' => fake()->sentence(10),
                        'location' => $location,
                        'ends_at' => $startsAt->copy()->addMinutes($minutes),
                    ],
                );
            }
        });
    }
}
