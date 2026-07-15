<?php

namespace App\Models;

use App\Enums\BudgetCategory;
use App\Enums\FinanceOperation;
use App\Enums\ProjectRole;
use App\Models\Scopes\OrganizationScope;
use App\Models\Scopes\ProjectScope;
use App\Observers\ProjectObserver;
use Database\Factories\ProjectFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[ObservedBy(ProjectObserver::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasSlug;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'beginning_date', 'end_date', 'project_type', 'description'])
            ->logOnlyDirty()
            ->useLogName('project')
            ->dontLogEmptyChanges();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->organization_id = $this->organization_id;
        $activity->project_id = $this->id;
    }

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'beginning_date',
        'end_date',
        'project_type',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'beginning_date' => 'date',
            'end_date' => 'date',
        ];
    }

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
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * @return MorphMany<Role, $this>
     */
    public function roles(): MorphMany
    {
        return $this->morphMany(Role::class, 'roleable');
    }

    public function roleFor(ProjectRole|string $role): ?Role
    {
        $name = $role instanceof ProjectRole ? $role->value : $role;

        return $this->roles()->where('name', $name)->first();
    }

    /**
     * @return array<int, string>
     */
    public function roleOptions(): array
    {
        return $this->roles()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Role $role): array => [
                $role->id => ProjectRole::tryFrom($role->name)?->getLabel() ?? $role->name,
            ])
            ->all();
    }

    /**
     * @return HasMany<FinanceEntry, $this>
     */
    public function financeEntries(): HasMany
    {
        return $this->hasMany(FinanceEntry::class);
    }

    /**
     * @return HasMany<ProjectCountry, $this>
     */
    public function countries(): HasMany
    {
        return $this->hasMany(ProjectCountry::class);
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
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

    /**
     * @return array<string, string> Category value => signed total
     */
    public function financeTotalsByCategory(): array
    {
        $totals = [];

        foreach (BudgetCategory::cases() as $category) {
            $query = $this->financeEntries()
                ->withoutGlobalScopes([OrganizationScope::class, ProjectScope::class])
                ->where('cost_category', $category->value);

            $added = (string) (clone $query)
                ->where('operation', FinanceOperation::Add->value)
                ->sum('amount');

            $subtracted = (string) (clone $query)
                ->where('operation', FinanceOperation::Subtract->value)
                ->sum('amount');

            $totals[$category->value] = bcsub($added ?: '0', $subtracted ?: '0', 2);
        }

        return $totals;
    }
}
