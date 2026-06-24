<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TravelExpense;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TravelExpensePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TravelExpense');
    }

    public function view(AuthUser $authUser, TravelExpense $travelExpense): bool
    {
        return $authUser->can('View:TravelExpense');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TravelExpense');
    }

    public function update(AuthUser $authUser, TravelExpense $travelExpense): bool
    {
        return $authUser->can('Update:TravelExpense');
    }

    public function delete(AuthUser $authUser, TravelExpense $travelExpense): bool
    {
        return $authUser->can('Delete:TravelExpense');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TravelExpense');
    }

    public function restore(AuthUser $authUser, TravelExpense $travelExpense): bool
    {
        return $authUser->can('Restore:TravelExpense');
    }

    public function forceDelete(AuthUser $authUser, TravelExpense $travelExpense): bool
    {
        return $authUser->can('ForceDelete:TravelExpense');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TravelExpense');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TravelExpense');
    }

    public function replicate(AuthUser $authUser, TravelExpense $travelExpense): bool
    {
        return $authUser->can('Replicate:TravelExpense');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TravelExpense');
    }
}
