<?php

namespace App\Models;

use App\Enums\Billing\TaxIdType;
use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Events\UserUpdated;
use App\Facades\ProjectAccess;
use App\ModalNotifications\Contracts\ModalDefinition;
use App\ModalNotifications\ModalPayload;
use App\ModalNotifications\Notifications\ModalDatabaseNotification;
use App\Models\Organization\OrganizationUser;
use App\Observers\UserObserver;
use App\Traits\User\HasAuthenticationMailable;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Laravel\Cashier\Billable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Propaganistas\LaravelPhone\Casts\RawPhoneNumberCast;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasMedia, HasTenants, MustVerifyEmail, PasskeyUser
{
    use Billable;
    use HasAuthenticationMailable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasOneTimePasswords;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;
    use PasskeyAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'username',
                'email',
                'phone',
                'locale',
                'default_organization_id',
                'invoice_email',
                'billing_company',
                'billing_country',
                'tax_id_type',
                'tax_id_value',
                'two_factor_confirmed_at',
            ])
            ->logOnlyDirty()
            ->useLogName('user')
            ->dontLogEmptyChanges();
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $activity->causer_type ??= self::class;
        $activity->causer_id ??= $this->getKey();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'username',
        'slug',
        'name',
        'email',
        'phone',
        'password',
        'default_organization_id',
        'date_of_birth',
        'locale',
        'invoice_email',
        'billing_company',
        'billing_line1',
        'billing_line2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'invoice_language',
        'invoice_purchase_order',
        'tax_id_type',
        'tax_id_value',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'updated' => UserUpdated::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'phone' => RawPhoneNumberCast::class.':ES',
            'tax_id_type' => TaxIdType::class,
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->using(OrganizationUser::class)
            ->as('member')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function roleFor(Organization|Project $tenant): ?Role
    {
        if ($tenant instanceof Organization) {
            $roleId = $this->organizations()->whereKey($tenant->getKey())->first()?->member->role_id;
        } else {
            $roleId = $this->projects()->whereKey($tenant->getKey())->first()?->pivot->role_id;
        }

        return $roleId !== null ? Role::with('permissions')->find($roleId) : null;
    }

    public function isOrgAdmin(Organization $organization): bool
    {
        return ProjectAccess::administersOrganization($this, $organization);
    }

    public function joinOrganization(Organization $organization, OrganizationRole|Role $role = OrganizationRole::Member): void
    {
        $roleId = $role instanceof Role
            ? $role->id
            : $organization->roleFor($role)?->id;

        $this->organizations()->syncWithoutDetaching([
            $organization->getKey() => ['role_id' => $roleId],
        ]);
    }

    public function joinProject(Project $project, ProjectRole $role = ProjectRole::Participant): void
    {
        $this->projects()->syncWithoutDetaching([
            $project->getKey() => ['role_id' => $project->roleFor($role)?->id],
        ]);
    }

    /**
     * @param  class-string<ModalDefinition<TPayload>>  $modalClass
     * @param  TPayload  $payload
     *
     * @template TPayload of ModalPayload
     */
    public function notifyWithModal(string $modalClass, ModalPayload $payload): void
    {
        if (! is_subclass_of($modalClass, ModalDefinition::class)) {
            throw new InvalidArgumentException(sprintf('[%s] is not a %s.', $modalClass, ModalDefinition::class));
        }

        $expected = $modalClass::payloadClass();

        if (! $payload instanceof $expected) {
            throw new InvalidArgumentException(sprintf('Modal [%s] expects payload of type [%s], got [%s].', $modalClass, $expected, $payload::class));
        }

        $this->notify(new ModalDatabaseNotification($modalClass, $payload));
    }

    /**
     * @return HasMany<OAuthAccount, $this>
     */
    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(OAuthAccount::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar') ?: null;
    }

    public function avatarUrl(): string
    {
        return $this->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&size=128';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'organization', 'user' => true,
            'organization.settings' => $this->organizations()->exists(),
            'project', 'project.settings' => $this->projects()->exists(),
            default => false,
        };
    }

    /**
     * @return Collection<int, Organization|Project>
     */
    public function getTenants(Panel $panel): Collection
    {
        return match ($panel->getId()) {
            'organization' => $this->organizations,
            'project' => $this->projectsForCurrentOrganization(),
            default => collect(),
        };
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return match (true) {
            $tenant instanceof Organization => $this->organizations()->whereKey($tenant->getKey())->exists(),
            $tenant instanceof Project => $this->projects()->whereKey($tenant->getKey())->exists(),
            default => false,
        };
    }

    /**
     * @return Collection<int, Project>
     */
    private function projectsForCurrentOrganization(): Collection
    {
        $organization = request()?->route('organization');

        if (! $organization instanceof Organization) {
            return collect();
        }

        return $this->projects()
            ->where('projects.organization_id', $organization->id)
            ->get();
    }

    public function hasVerifiedEmail()
    {
        return true;
    }
}
