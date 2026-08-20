<?php

namespace App\Console\Commands;

use App\Models\Project\TravelExpenseAiExtraction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('travel-expense-extractions:prune {--hours=24}')]
#[Description('Delete travel expense AI extractions and their staged files older than a given number of hours (default 24).')]
class PruneTravelExpenseAiExtractions extends Command
{
    public function handle(): int
    {
        $hours = max((int) $this->option('hours'), 1);
        $cutoff = now()->subHours($hours);

        $sessionIds = TravelExpenseAiExtraction::query()
            ->where('created_at', '<', $cutoff)
            ->pluck('session_id')
            ->unique()
            ->all();

        $deleted = TravelExpenseAiExtraction::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        foreach ($sessionIds as $sessionId) {
            Storage::disk('local')->deleteDirectory('ai-extractions/'.$sessionId);
        }

        $this->info("Pruned {$deleted} extraction(s) older than {$hours}h.");

        return self::SUCCESS;
    }
}
