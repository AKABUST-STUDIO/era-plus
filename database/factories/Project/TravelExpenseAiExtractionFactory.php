<?php

namespace Database\Factories\Project;

use App\Enums\Project\TravelExpense\AiExtractionTarget;
use App\Models\Project;
use App\Models\Project\TravelExpenseAiExtraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TravelExpenseAiExtraction>
 */
class TravelExpenseAiExtractionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'target' => AiExtractionTarget::Journey,
            'fingerprint' => null,
            'file_paths' => [],
            'extracted_data' => null,
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ];
    }

    public function completed(array $data): self
    {
        return $this->state(fn (): array => [
            'completed_at' => now(),
            'extracted_data' => $data,
        ]);
    }

    public function failed(string $message = 'error'): self
    {
        return $this->state(fn (): array => [
            'failed_at' => now(),
            'error_message' => $message,
        ]);
    }
}
