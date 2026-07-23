<?php

namespace App\Models;

use App\Enums\ProjectTask\ProjectTaskStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToProject;
use Database\Factories\ProjectTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    /** @use HasFactory<ProjectTaskFactory> */
    use BelongsToOrganization;

    use BelongsToProject;
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'assigned_to',
        'assigned_by',
        'title',
        'description',
        'status',
        'due_date',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectTaskStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $task): void {
            if ($task->status === ProjectTaskStatus::Completed && $task->completed_at === null) {
                $task->completed_at = now();
            }

            if ($task->status !== ProjectTaskStatus::Completed) {
                $task->completed_at = null;
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isOverdue(): bool
    {
        if ($this->status === ProjectTaskStatus::Completed) {
            return false;
        }

        return $this->due_date !== null && $this->due_date->isPast();
    }
}
