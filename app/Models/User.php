<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Facades\ProjectAccess;
use App\Observers\UserObserver;
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
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Cashier\Billable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasMedia, HasTenants, MustVerifyEmail
{
    use Billable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'default_organization_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
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
        return $this->belongsToMany(Organization::class, 'organization_user')
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
        $relation = $tenant instanceof Organization ? $this->organizations() : $this->projects();

        $roleId = $relation->whereKey($tenant->getKey())->first()?->pivot->role_id;

        return $roleId !== null ? Role::find($roleId) : null;
    }

    public function isOrgAdmin(Organization $organization): bool
    {
        return ProjectAccess::administersOrganization($this, $organization);
    }

    public function joinOrganization(Organization $organization, OrganizationRole $role = OrganizationRole::Member): void
    {
        $this->organizations()->syncWithoutDetaching([
            $organization->getKey() => ['role_id' => $organization->roleFor($role)?->id],
        ]);
    }

    public function joinProject(Project $project, ProjectRole $role = ProjectRole::Participant): void
    {
        $this->projects()->syncWithoutDetaching([
            $project->getKey() => ['role_id' => $project->roleFor($role)?->id],
        ]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 256, 256)
            ->nonQueued();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb') ?: null;
    }

    public function avatarUrl(): string
    {
        return $this->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&size=128';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'organization' => true,
            'project' => $this->projects()->exists(),
            'user' => true,
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
