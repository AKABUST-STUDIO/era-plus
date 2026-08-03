<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use Illuminate\Database\Seeder;
use Nnjeim\World\Models\Country;

class ParticipantSeeder extends Seeder
{
    private const COUNTRIES = ['ES', 'IT', 'PT', 'DE', 'PL'];

    private const PER_COUNTRY = 3;

    public function run(): void
    {
        $project = Project::query()->first();

        if ($project === null) {
            $this->command->warn('No project to attach participants to.');

            return;
        }

        foreach (self::COUNTRIES as $iso2) {
            $country = Country::query()->where('iso2', $iso2)->first();

            if ($country === null) {
                $this->command->warn("Country {$iso2} is missing — run the WorldSeeder first.");

                continue;
            }

            $sendingOrganization = ParticipantOrganization::firstOrCreate([
                'name' => $country->name.' '.fake()->randomElement(['University', 'Institute', 'Academy']),
            ]);

            Participant::factory()
                ->count(self::PER_COUNTRY)
                ->create()
                ->each(fn (Participant $participant) => $project->addParticipant(
                    $participant,
                    $country->id,
                    $sendingOrganization,
                ));
        }

        $this->command->info('Seeded '.(count(self::COUNTRIES) * self::PER_COUNTRY).' participants on "'.$project->name.'".');
    }
}
