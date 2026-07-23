<?php

namespace Database\Factories;

use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actionType = ErasmusActionType::Ka152;

        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company(),
            'erasmus_field' => $actionType->fields()[0],
            'erasmus_key_action' => $actionType->keyAction(),
            'erasmus_action' => $actionType,
            'erasmus_managing_body' => $actionType->managingBody(),
            'status' => ProjectStatus::Draft,
            'call_year' => 2026,
        ];
    }

    public function ofActionType(ErasmusActionType $actionType, ?ErasmusField $erasmusField = null): self
    {
        return $this->state(fn (): array => [
            'erasmus_field' => $erasmusField ?? $actionType->fields()[0],
            'erasmus_key_action' => $actionType->keyAction(),
            'erasmus_action' => $actionType,
            'erasmus_managing_body' => $actionType->managingBody(),
        ]);
    }
}
