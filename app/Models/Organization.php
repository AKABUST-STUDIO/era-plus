<?php

namespace App\Models;

use App\Enums\SubscriptionTier;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Organization extends Model implements HasAvatar, HasMedia
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use Billable;

    use HasFactory;
    use HasSlug;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'subscription_tier', 'enforce_two_factor', 'enforce_email_verification', 'tax_id'])
            ->logOnlyDirty()
            ->useLogName('organization')
            ->dontLogEmptyChanges();
    }

    public function tapActivity(\Spatie\Activitylog\Contracts\Activity $activity, string $eventName): void
    {
        $activity->organization_id = $this->id;
    }

    protected static function booted(): void
    {
        static::created(function (self $organization): void {
            $organization->subscribeToFreePlan();
        });

        static::deleting(function (self $organization): void {
            if ($organization->isForceDeleting()) {
                return;
            }

            $organization->cancelAllSubscriptions();
        });
    }

    /**
     * Attach the Free-tier Stripe subscription on Organization creation.
     * Silently no-ops when the price ID or Stripe credentials are missing
     * (local/test) — the org is still created with subscription_tier=free.
     */
    public function subscribeToFreePlan(): void
    {
        $priceId = config('services.stripe.prices.free');
        $secret = config('cashier.secret');

        if (! $priceId || ! $secret) {
            return;
        }

        try {
            $this->newSubscription('default', $priceId)->create();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Cancel every active Stripe subscription for this organization.
     * Wrapped so test environments without Stripe creds don't break delete.
     */
    public function cancelAllSubscriptions(): void
    {
        if (! $this->hasStripeId()) {
            return;
        }

        try {
            $this->subscriptions()
                ->active()
                ->get()
                ->each(fn ($subscription) => $subscription->cancelNow());
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected $fillable = [
        'name',
        'slug',
        'subscription_tier',
        'extra_project_seats',
        'billing_address',
        'invoice_language',
        'tax_id',
        'enforce_two_factor',
        'enforce_email_verification',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'subscription_tier' => 'free',
        'extra_project_seats' => 0,
        'invoice_language' => 'en',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subscription_tier' => SubscriptionTier::class,
            'extra_project_seats' => 'integer',
            'trial_ends_at' => 'datetime',
            'billing_address' => 'array',
            'enforce_two_factor' => 'boolean',
            'enforce_email_verification' => 'boolean',
        ];
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
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('role', 'is_admin')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivot('is_admin', true);
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
        $base = $this->subscription_tier?->baseProjectLimit();

        if ($base === null) {
            return null;
        }

        return $base + (int) $this->extra_project_seats;
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
