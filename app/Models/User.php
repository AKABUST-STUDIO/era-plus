<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'organization' => $this->organizations()->exists(),
            'project' => $this->projects()->exists(),
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
}
