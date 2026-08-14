<?php

namespace App\Models;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Subscription\SubscriptionTier;
use App\Models\Organization\OrganizationUser;
use App\Observers\OrganizationObserver;
use App\Policies\OrganizationPolicy;
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

    public function beforeActivityLogged(Activity $activity, string $eventName): void
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
        return Attribute::get(fn (): SubscriptionTier => $this->subscription?->tier() ?? SubscriptionTier::Basic);
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
            $query->from('roles')
                ->whereColumn('roles.id', 'organization_users.role_id')
                ->where('roles.name', OrganizationRole::Admin->value);
        });
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function purchasedProjectSlots(): int
    {
        $slotPriceId = config('services.stripe.prices.project_slot');

        if (! is_string($slotPriceId) || $slotPriceId === '') {
            return 0;
        }

        return (int) ($this->subscription?->items->firstWhere('stripe_price', $slotPriceId)?->quantity ?? 0);
    }

    public function freeProjectAllowance(): int
    {
        $freePriceId = config('services.stripe.prices.project_free');

        if (! is_string($freePriceId) || $freePriceId === '') {
            return 1;
        }

        $item = $this->subscription?->items->firstWhere('stripe_price', $freePriceId);

        return $item === null ? 0 : (int) ($item->quantity ?? 1);
    }

    public function projectLimit(): int
    {
        return $this->freeProjectAllowance() + $this->purchasedProjectSlots();
    }

    public function activeProjectCount(): int
    {
        return $this->projects()->count();
    }

    public function canCreateProject(): bool
    {
        return $this->activeProjectCount() < $this->projectLimit();
    }

    public function canReduceProjectSlotsTo(int $newQuantity): bool
    {
        return $newQuantity + $this->freeProjectAllowance() >= $this->activeProjectCount();
    }
}
