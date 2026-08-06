<?php

declare(strict_types=1);

namespace App\Policies\Project;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class CountryLimitPolicy
{
    public function update(User $user): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, ProjectAccess::ABILITY_MANAGE_COUNTRY_LIMITS, $project);
    }
}
