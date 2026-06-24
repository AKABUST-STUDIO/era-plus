<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityLogFactory> */
    use BelongsToOrganization;

    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'project_id',
        'user_id',
        'event_type',
        'label',
        'target_type',
        'target_id',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
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

    /**
     * @param  array<string, mixed>  $data
     */
    public static function record(
        Organization $organization,
        string $label,
        ?Project $project = null,
        ?string $eventType = null,
        ?Model $target = null,
        array $data = [],
    ): self {
        return self::create([
            'organization_id' => $organization->id,
            'project_id' => $project?->id,
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'label' => $label,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'data' => $data ?: null,
            'created_at' => now(),
        ]);
    }
}
