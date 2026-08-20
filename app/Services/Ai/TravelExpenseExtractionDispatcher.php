<?php

namespace App\Services\Ai;

use App\Enums\Project\TravelExpense\AiExtractionTarget;
use App\Jobs\RunTravelExpenseExtraction;
use App\Models\Project;
use App\Models\Project\TravelExpenseAiExtraction;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TravelExpenseExtractionDispatcher
{
    /**
     * @param  array<mixed>  $state
     */
    public static function handleUpload(
        string $sessionId,
        AiExtractionTarget $target,
        Project $project,
        User $user,
        array $state,
    ): void {
        $files = self::pluckTemporaryFiles($state);

        if ($files === []) {
            self::forget($sessionId, $target);

            return;
        }

        $fingerprint = self::fingerprintFor($files);

        $existing = TravelExpenseAiExtraction::query()
            ->where('session_id', $sessionId)
            ->where('target', $target)
            ->first();

        if ($existing !== null && $existing->fingerprint === $fingerprint) {
            return;
        }

        self::wipeFiles($sessionId, $target);
        $paths = self::persistFiles($sessionId, $target, $files);

        $extraction = TravelExpenseAiExtraction::updateOrCreate(
            ['session_id' => $sessionId, 'target' => $target],
            [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'file_paths' => $paths,
                'fingerprint' => $fingerprint,
                'completed_at' => null,
                'failed_at' => null,
                'extracted_data' => null,
                'error_message' => null,
            ],
        );

        RunTravelExpenseExtraction::dispatch($extraction->id);
    }

    public static function forget(string $sessionId, AiExtractionTarget $target): void
    {
        self::wipeFiles($sessionId, $target);

        TravelExpenseAiExtraction::query()
            ->where('session_id', $sessionId)
            ->where('target', $target)
            ->delete();
    }

    public static function newSessionId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * @param  array<mixed>  $state
     * @return array<int, TemporaryUploadedFile>
     */
    private static function pluckTemporaryFiles(array $state): array
    {
        $files = [];

        foreach ($state as $item) {
            if ($item instanceof TemporaryUploadedFile) {
                $files[] = $item;
            }
        }

        return $files;
    }

    /**
     * @param  array<int, TemporaryUploadedFile>  $files
     */
    private static function fingerprintFor(array $files): string
    {
        $names = array_map(fn (TemporaryUploadedFile $file): string => $file->getFilename(), $files);
        sort($names);

        return substr(hash('sha256', implode('|', $names)), 0, 40);
    }

    /**
     * @param  array<int, TemporaryUploadedFile>  $files
     * @return array<int, string>
     */
    private static function persistFiles(string $sessionId, AiExtractionTarget $target, array $files): array
    {
        $directory = self::directory($sessionId, $target);
        $disk = Storage::disk('local');
        $disk->makeDirectory($directory);

        $paths = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $disk->put($directory.'/'.$filename, $file->get());
            $paths[] = $disk->path($directory.'/'.$filename);
        }

        return $paths;
    }

    private static function wipeFiles(string $sessionId, AiExtractionTarget $target): void
    {
        Storage::disk('local')->deleteDirectory(self::directory($sessionId, $target));
    }

    private static function directory(string $sessionId, AiExtractionTarget $target): string
    {
        return 'ai-extractions/'.$sessionId.'/'.$target->value;
    }
}
