<?php

namespace App\Models\Project;

use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\User;
use App\Policies\Project\ProjectParticipantPolicy;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Nnjeim\World\Models\Country;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[UsePolicy(ProjectParticipantPolicy::class)]
class ProjectParticipant extends Model
{
    use LogsActivity;

    protected $table = 'project_participant';

    protected $fillable = [
        'project_id',
        'participable_type',
        'participable_id',
        'country_id',
        'sending_organization_type',
        'sending_organization_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'project_id',
                'participable_type',
                'participable_id',
                'country_id',
                'sending_organization_type',
                'sending_organization_id',
            ])
            ->logOnlyDirty()
            ->useLogName('project_participant')
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
     * @return MorphTo<Model, $this>
     */
    public function participable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isVerified(): bool
    {
        return $this->participable_type === User::class;
    }

    public function avatarUrl(): string
    {
        $participable = $this->participable;

        if ($participable !== null && method_exists($participable, 'avatarUrl')) {
            return $participable->avatarUrl();
        }

        $name = (string) ($participable?->name ?? '?');

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&size=128';
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function sendingOrganization(): MorphTo
    {
        return $this->morphTo('sending_organization');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<TravelExpense, $this>
     */
    public function travelExpenses(): HasMany
    {
        return $this->hasMany(TravelExpense::class);
    }
}
