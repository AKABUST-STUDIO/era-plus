<?php

namespace App\Models\Project;

use App\Models\Organization;
use App\Models\Project;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ErasmusPriority extends Model
{
    protected $table = 'priorities';

    protected $fillable = [
        'organization_id',
        'name',
    ];

    protected static function booted(): void
    {
        static::creating(function (ErasmusPriority $priority): void {
            $priority->organization_id ??= self::organizationIdFor(Filament::getTenant());
        });
    }

    public static function organizationIdFor(?Model $tenant): ?int
    {
        return $tenant instanceof Organization
            ? $tenant->getKey()
            : $tenant?->organization_id;
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'priority_project', 'priority_id', 'project_id');
    }

    /**
     * @param  Builder<ErasmusPriority>  $query
     */
    public function scopeVisibleTo(Builder $query, ?Model $tenant): void
    {
        $organizationId = self::organizationIdFor($tenant);

        $query->where(function (Builder $query) use ($organizationId): void {
            $query->whereNull('organization_id')
                ->when($organizationId, fn (Builder $query) => $query->orWhere('organization_id', $organizationId));
        });
    }
}
