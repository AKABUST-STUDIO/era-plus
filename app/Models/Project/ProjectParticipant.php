<?php

namespace App\Models\Project;

use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Nnjeim\World\Models\Country;

class ProjectParticipant extends Model
{
    protected $table = 'project_participant';

    protected $fillable = [
        'project_id',
        'participable_type',
        'participable_id',
        'country_id',
        'sending_organization_type',
        'sending_organization_id',
    ];

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
}
