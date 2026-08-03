<?php

namespace Database\Seeders;

use App\Enums\Project\TransportationType;
use App\Enums\Project\TravelType;
use App\Models\Project\CountryLimit;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class TravelExpenseSeeder extends Seeder
{
    /**
     * @var array<string, array{int, int}>
     */
    private const COST_RANGES = [
        'train' => [40, 180],
        'public_transport' => [5, 40],
        'car' => [30, 200],
        'flight' => [90, 450],
        'other' => [20, 120],
    ];

    public function run(): void
    {
        $participations = ProjectParticipant::withoutGlobalScopes()
            ->where('participable_type', (new Participant)->getMorphClass())
            ->whereDoesntHave('travelExpenses')
            ->get();

        if ($participations->isEmpty()) {
            $this->command->warn('No participations without expenses — nothing to seed.');

            return;
        }

        foreach ($participations as $participation) {
            foreach (TravelType::cases() as $travelType) {
                $transportationType = fake()->randomElement(TransportationType::cases());
                [$min, $max] = self::COST_RANGES[$transportationType->value];

                TravelExpense::factory()
                    ->forParticipant($participation)
                    ->costing(fake()->randomFloat(2, $min, $max))
                    ->create([
                        'travel_type' => $travelType,
                        'transportation_type' => $transportationType,
                    ]);
            }
        }

        $this->seedLimits($participations);

        $this->command->info('Seeded expenses for '.$participations->count().' participant(s).');
    }

    /**
     * @param  Collection<int, ProjectParticipant>  $participations
     */
    private function seedLimits(Collection $participations): void
    {
        $totals = TravelExpense::withoutGlobalScopes()
            ->whereIn('project_participant_id', $participations->pluck('id'))
            ->selectRaw('project_participant_id, SUM(cost_eur) AS total')
            ->groupBy('project_participant_id')
            ->pluck('total', 'project_participant_id');

        $participations
            ->groupBy(fn (ProjectParticipant $row): string => $row->project_id.':'.$row->country_id)
            ->each(function (Collection $group, string $key) use ($totals): void {
                [$projectId, $countryId] = explode(':', $key);

                $median = $group
                    ->map(fn (ProjectParticipant $row): float => (float) ($totals[$row->id] ?? 0))
                    ->sort()
                    ->values()
                    ->get((int) floor($group->count() / 2));

                CountryLimit::updateOrCreate(
                    ['project_id' => (int) $projectId, 'country_id' => (int) $countryId],
                    ['amount_eur' => round((float) $median, -1)],
                );
            });
    }
}
