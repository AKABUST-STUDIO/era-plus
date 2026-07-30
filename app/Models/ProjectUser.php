<?php

namespace App\Models;

use App\Models\Scopes\ProjectScope;
use App\Policies\ProjectUserPolicy;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[UsePolicy(ProjectUserPolicy::class)]
class ProjectUser extends Pivot
{
    use LogsActivity;

    protected $table = 'project_user';

    public $incrementing = true;

    protected $fillable = [
        'project_id',
        'user_id',
        'role_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['project_id', 'user_id', 'role_id'])
            ->logOnlyDirty()
            ->useLogName('project_member')
            ->dontLogEmptyChanges();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->project_id = $this->project_id;

        if ($project = $this->project) {
            $activity->organization_id = $project->organization_id;
        }
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new ProjectScope);

        static::creating(function (self $member): void {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Project && empty($member->project_id)) {
                $member->project_id = $tenant->id;
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
