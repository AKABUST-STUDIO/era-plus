<?php

namespace App\Models\Project;

use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Policies\Project\CountryLimitPolicy;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nnjeim\World\Models\Country;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[UsePolicy(CountryLimitPolicy::class)]
class CountryLimit extends Model
{
    use LogsActivity;

    protected $table = 'country_limits';

    protected $fillable = [
        'project_id',
        'country_id',
        'amount_eur',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_eur' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'project_id',
                'country_id',
                'amount_eur',
            ])
            ->logOnlyDirty()
            ->useLogName('country_limit')
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

        static::creating(function (self $limit): void {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Project && empty($limit->project_id)) {
                $limit->project_id = $tenant->id;
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
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
