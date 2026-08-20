<?php

namespace App\Models\Project;

use App\Enums\Project\TravelExpense\AiExtractionTarget;
use App\Models\Project;
use App\Models\User;
use Database\Factories\Project\TravelExpenseAiExtractionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelExpenseAiExtraction extends Model
{
    /** @use HasFactory<TravelExpenseAiExtractionFactory> */
    use HasFactory;

    protected $table = 'travel_expense_ai_extractions';

    protected $fillable = [
        'session_id',
        'user_id',
        'project_id',
        'target',
        'fingerprint',
        'completed_at',
        'failed_at',
        'file_paths',
        'extracted_data',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target' => AiExtractionTarget::class,
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'file_paths' => 'array',
            'extracted_data' => 'array',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->completed_at !== null => 'completed',
            $this->failed_at !== null => 'failed',
            default => 'pending',
        });
    }

    public function markCompleted(array $data): void
    {
        $this->forceFill([
            'extracted_data' => $data,
            'completed_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'failed_at' => now(),
            'completed_at' => null,
            'error_message' => $message,
        ])->save();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
