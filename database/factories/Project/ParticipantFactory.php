<?php

namespace Database\Factories\Project;

use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use App\Models\Project\ProjectParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'date_of_birth' => fake()->optional()->dateTimeBetween('-40 years', '-16 years'),
        ];
    }

    public function participatingIn(Project $project, ?int $countryId = null, ?ParticipantOrganization $sendingOrganization = null): self
    {
        return $this->afterCreating(function (Participant $participant) use ($project, $countryId, $sendingOrganization): void {
            $sending = $sendingOrganization ?? ParticipantOrganization::create(['name' => fake()->company()]);

            ProjectParticipant::create([
                'project_id' => $project->id,
                'participable_type' => $participant->getMorphClass(),
                'participable_id' => $participant->id,
                'country_id' => $countryId ?? self::ensureCountryId(),
                'sending_organization_type' => $sending->getMorphClass(),
                'sending_organization_id' => $sending->getKey(),
            ]);
        });
    }

    private static function ensureCountryId(): int
    {
        $existing = DB::table('countries')->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        DB::table('countries')->insert([
            'iso2' => 'ES',
            'name' => 'Spain',
            'status' => 1,
            'phone_code' => '0',
            'iso3' => 'ESP',
            'region' => 'Europe',
            'subregion' => 'Europe',
        ]);

        return (int) DB::table('countries')->where('iso2', 'ES')->value('id');
    }
}
