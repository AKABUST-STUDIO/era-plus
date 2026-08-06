<?php

namespace App\Models;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Subscription\SubscriptionTier;
use App\Models\Organization\OrganizationUser;
use App\Observers\OrganizationObserver;
use App\Policies\OrganizationPolicy;
use App\Services\ProjectAccess;
use Database\Factories\OrganizationFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[ObservedBy(OrganizationObserver::class)]
#[UsePolicy(OrganizationPolicy::class)]
class Organization extends Model implements HasAvatar, HasMedia
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasSlug;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'enforce_two_factor', 'enforce_email_verification'])
            ->logOnlyDirty()
            ->useLogName('organization')
            ->dontLogEmptyChanges();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->organization_id = $this->id;
    }

    protected $fillable = [
        'name',
        'slug',
        'subscription_id',
        'enforce_two_factor',
        'enforce_email_verification',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enforce_two_factor' => 'boolean',
            'enforce_email_verification' => 'boolean',
        ];
    }

    protected function subscriptionTier(): Attribute
    {
        return Attribute::get(fn (): SubscriptionTier => SubscriptionTier::fromStripePriceId($this->subscription?->stripe_price) ?? SubscriptionTier::Basic);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 256, 256)
            ->nonQueued();
    }

    public function getAvatarUrl(): string
    {
        return Filament::getTenantAvatarUrl($this);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb') ?: null;
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function owner(): ?User
    {
        return $this->subscription?->user;
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->using(OrganizationUser::class)
            ->as('member')
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

    public function roleFor(OrganizationRole|string $role): ?Role
    {
        $name = $role instanceof OrganizationRole ? $role->value : $role;

        return $this->roles()->where('name', $name)->first();
    }

    public function defaultMemberRole(): ?Role
    {
        return $this->roleFor(OrganizationRole::Member);
    }

    /**
     * @return array<int, string>
     */
    public function roleOptions(): array
    {
        return $this->roles()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Role $role): array => [$role->id => $role->displayLabel()])
            ->all();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->users()->whereExists(function (QueryBuilder $query): void {
            $query->from('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->whereColumn('role_has_permissions.role_id', 'organization_users.role_id')
                ->where('permissions.name', ProjectAccess::ABILITY_ADMINISTER_ORGANIZATION);
        });
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function projectLimit(): ?int
    {
        return $this->subscription_tier->baseProjectLimit();
    }

    public function canCreateProject(): bool
    {
        $limit = $this->projectLimit();

        if ($limit === null) {
            return true;
        }

        return $this->projects()->count() < $limit;
    }
}
