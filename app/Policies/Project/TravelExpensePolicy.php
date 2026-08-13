<?php

declare(strict_types=1);

namespace App\Policies\Project;

use App\Enums\Permissions\TravelExpensePermission;
use App\Models\Project;
use App\Models\Project\TravelExpense;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class TravelExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, TravelExpensePermission::ViewAny->value);
    }

    public function view(User $user, TravelExpense $travelExpense): bool
    {
        return $this->allows($user, TravelExpensePermission::ViewAny->value);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, TravelExpensePermission::Create->value);
    }

    public function update(User $user, TravelExpense $travelExpense): bool
    {
        return $this->allows($user, TravelExpensePermission::Update->value);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, TravelExpensePermission::UpdateAny->value);
    }

    public function delete(User $user, TravelExpense $travelExpense): bool
    {
        return $this->allows($user, TravelExpensePermission::Delete->value);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, TravelExpensePermission::DeleteAny->value);
    }

    private function allows(User $user, string $permission): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $permission, $project);
    }
}
