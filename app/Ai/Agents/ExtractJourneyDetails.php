<?php

namespace App\Ai\Agents;

use App\Enums\Project\TransportationType;
use App\Enums\Project\TravelType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5')]
class ExtractJourneyDetails implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
            You are an expert at extracting structured travel information from documents such as
            boarding passes, e-tickets, train tickets, bus tickets and travel itineraries.

            Given one or more attached documents describing a single trip, extract the following
            fields. Return null for any field you cannot confidently determine — do not guess.

            - date: the travel date in ISO YYYY-MM-DD format.
            - from: the origin city or station (short, human-readable, no codes).
            - to: the destination city or station (short, human-readable, no codes).
            - travel_type: "departure" for outbound trips, "return" for the trip home.
              If unclear, prefer "departure".
            - transportation_type: one of train, public_transport, car, flight, other.

            Only return the structured JSON — do not add commentary.
            INSTRUCTIONS;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->nullable(),
            'from' => $schema->string()->nullable(),
            'to' => $schema->string()->nullable(),
            'travel_type' => $schema->string()->enum(TravelType::class)->nullable(),
            'transportation_type' => $schema->string()->enum(TransportationType::class)->nullable(),
        ];
    }
}
