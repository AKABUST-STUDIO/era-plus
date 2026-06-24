<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectEvent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProjectEvent');
    }

    public function view(AuthUser $authUser, ProjectEvent $projectEvent): bool
    {
        return $authUser->can('View:ProjectEvent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProjectEvent');
    }

    public function update(AuthUser $authUser, ProjectEvent $projectEvent): bool
    {
        return $authUser->can('Update:ProjectEvent');
    }

    public function delete(AuthUser $authUser, ProjectEvent $projectEvent): bool
    {
        return $authUser->can('Delete:ProjectEvent');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProjectEvent');
    }

    public function restore(AuthUser $authUser, ProjectEvent $projectEvent): bool
    {
        return $authUser->can('Restore:ProjectEvent');
    }

    public function forceDelete(AuthUser $authUser, ProjectEvent $projectEvent): bool
    {
        return $authUser->can('ForceDelete:ProjectEvent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProjectEvent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProjectEvent');
    }

    public function replicate(AuthUser $authUser, ProjectEvent $projectEvent): bool
    {
        return $authUser->can('Replicate:ProjectEvent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProjectEvent');
    }
}
