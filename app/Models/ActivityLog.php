<?php

namespace App\Models;

use App\Policies\ActivityLogPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

#[UsePolicy(ActivityLogPolicy::class)]
class ActivityLog extends Activity
{
    public $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'collection',
            'attribute_changes' => 'collection',
        ];
    }

    public function getLabelAttribute(): string
    {
        return (string) $this->description;
    }

    public function getEventTypeAttribute(): ?string
    {
        return $this->event;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id')
            ->where('causer_type', User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
        $builder = activity()->withProperties($data);

        if ($eventType !== null) {
            $builder = $builder->event($eventType);
        }

        if ($causer = auth()->user()) {
            $builder = $builder->causedBy($causer);
        }

        if ($target !== null) {
            $builder = $builder->performedOn($target);
        }

        $log = $builder->log($label);

        $entry = self::query()->findOrFail($log->id);
        $entry->forceFill([
            'organization_id' => $organization->id,
            'project_id' => $project?->id,
        ])->save();

        return $entry;
    }
}
