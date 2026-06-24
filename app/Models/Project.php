<?php

namespace App\Models;

use App\Enums\FinanceOperation;
use App\Models\Scopes\OrganizationScope;
use App\Models\Scopes\ProjectScope;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    use HasSlug;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getAvatarUrl(): string
    {
        return Filament::getTenantAvatarUrl($this);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field = $field ?? $this->getRouteKeyName();
        $query = $this->where($field, $value);

        $organization = request()?->route('organization');

        if ($organization instanceof Organization) {
            $query->where('organization_id', $organization->id);
        }

        return $query->first();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<FinanceEntry, $this>
     */
    public function financeEntries(): HasMany
    {
        return $this->hasMany(FinanceEntry::class);
    }

    public function financeTotal(): string
    {
        $query = $this->financeEntries()
            ->withoutGlobalScopes([OrganizationScope::class, ProjectScope::class]);

        $added = (string) (clone $query)
            ->where('operation', FinanceOperation::Add->value)
            ->sum('amount');

        $subtracted = (string) (clone $query)
            ->where('operation', FinanceOperation::Subtract->value)
            ->sum('amount');

        return bcsub($added ?: '0', $subtracted ?: '0', 2);
    }
}
