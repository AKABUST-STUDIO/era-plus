<?php

namespace App\Models\Project;

use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\User;
use App\Policies\Project\ProjectEventPolicy;
use Database\Factories\Project\ProjectEventFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[UsePolicy(ProjectEventPolicy::class)]
class ProjectEvent extends Model
{
    /** @use HasFactory<ProjectEventFactory> */
    use HasFactory;

    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'project_events';

    protected $fillable = [
        'project_id',
        'google_event_id',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'created_by',
        'google_updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'google_updated_at' => 'datetime',
        ];
    }

    public function getAllDayAttribute(): bool
    {
        if ($this->starts_at === null || $this->ends_at === null) {
            return false;
        }

        return $this->starts_at->format('H:i:s') === '00:00:00'
            && in_array($this->ends_at->format('H:i:s'), ['00:00:00', '23:59:59'], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'description',
                'location',
                'starts_at',
                'ends_at',
            ])
            ->logOnlyDirty()
            ->useLogName('project_event')
            ->dontLogEmptyChanges();
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $activity->project_id = $this->project_id;

        if ($project = $this->project) {
            $activity->organization_id = $project->organization_id;
        }
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new ProjectScope);

        static::creating(function (self $row): void {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Project && empty($row->project_id)) {
                $row->project_id = $tenant->id;
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ProjectEventAttendee, $this>
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(ProjectEventAttendee::class, 'project_event_id');
    }

    /**
     * @return BelongsToMany<ProjectParticipant, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectParticipant::class,
            'project_event_attendees',
            'project_event_id',
            'project_participant_id',
        )
            ->withPivot(['id', 'response_status'])
            ->withTimestamps();
    }
}
