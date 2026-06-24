<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FinanceEntry;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FinanceEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinanceEntry');
    }

    public function view(AuthUser $authUser, FinanceEntry $financeEntry): bool
    {
        return $authUser->can('View:FinanceEntry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinanceEntry');
    }

    public function update(AuthUser $authUser, FinanceEntry $financeEntry): bool
    {
        return $authUser->can('Update:FinanceEntry');
    }

    public function delete(AuthUser $authUser, FinanceEntry $financeEntry): bool
    {
        return $authUser->can('Delete:FinanceEntry');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FinanceEntry');
    }

    public function restore(AuthUser $authUser, FinanceEntry $financeEntry): bool
    {
        return $authUser->can('Restore:FinanceEntry');
    }

    public function forceDelete(AuthUser $authUser, FinanceEntry $financeEntry): bool
    {
        return $authUser->can('ForceDelete:FinanceEntry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinanceEntry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinanceEntry');
    }

    public function replicate(AuthUser $authUser, FinanceEntry $financeEntry): bool
    {
        return $authUser->can('Replicate:FinanceEntry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinanceEntry');
    }
}
