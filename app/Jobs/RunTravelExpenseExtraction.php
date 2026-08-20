<?php

namespace App\Jobs;

use App\Ai\Agents\ExtractJourneyDetails;
use App\Ai\Agents\ExtractPaymentDetails;
use App\Enums\Project\TravelExpense\AiExtractionTarget;
use App\Models\Project\TravelExpenseAiExtraction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files;
use Throwable;

class RunTravelExpenseExtraction implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(public int $extractionId)
    {
        $this->onQueue('ai-extraction');
    }

    public function handle(): void
    {
        $extraction = TravelExpenseAiExtraction::query()->find($this->extractionId);

        if ($extraction === null) {
            return;
        }

        try {
            $attachments = $this->buildAttachments($extraction->file_paths);

            if ($attachments === []) {
                $extraction->markFailed('No readable files were found for AI extraction.');

                return;
            }

            $agent = match ($extraction->target) {
                AiExtractionTarget::Journey => new ExtractJourneyDetails,
                AiExtractionTarget::Payment => new ExtractPaymentDetails,
            };

            $response = $agent->prompt(
                'Extract the requested fields from the attached documents.',
                attachments: $attachments,
            );

            $extraction->markCompleted($this->normalize($response->toArray()));
        } catch (Throwable $e) {
            Log::error('Travel expense AI extraction failed', [
                'extraction_id' => $extraction->id,
                'target' => $extraction->target->value,
                'exception' => $e,
            ]);

            $extraction->markFailed($e->getMessage());
        }
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, Files\LocalImage|Files\LocalDocument>
     */
    protected function buildAttachments(array $paths): array
    {
        $attachments = [];

        foreach ($paths as $path) {
            if (! is_string($path) || ! is_file($path)) {
                continue;
            }

            $mime = mime_content_type($path) ?: '';

            $attachments[] = str_starts_with($mime, 'image/')
                ? Files\Image::fromPath($path)
                : Files\Document::fromPath($path);
        }

        return $attachments;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalize(array $data): array
    {
        return array_filter(
            $data,
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }
}
