<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\TravelExpense;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class TravelExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public function view(User $user, TravelExpense $travelExpense): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public function update(User $user, TravelExpense $travelExpense): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public function delete(User $user, TravelExpense $travelExpense): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public function restore(User $user, TravelExpense $travelExpense): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public function forceDelete(User $user, TravelExpense $travelExpense): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    private function allows(User $user, string $ability): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $ability, $project);
    }
}
