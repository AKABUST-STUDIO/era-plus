<?php

declare(strict_types=1);

namespace App\Policies\Project;

use App\Models\Project;
use App\Models\Project\CountryLimit;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class CountryLimitPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_COUNTRY_LIMITS);
    }

    public function view(User $user, CountryLimit $countryLimit): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_COUNTRY_LIMITS);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_COUNTRY_LIMITS);
    }

    public function update(User $user, CountryLimit $countryLimit): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_COUNTRY_LIMITS);
    }

    public function delete(User $user, CountryLimit $countryLimit): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_COUNTRY_LIMITS);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_COUNTRY_LIMITS);
    }

    private function allows(User $user, string $ability): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $ability, $project);
    }
}
