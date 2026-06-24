<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectMember;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectMemberPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProjectMember');
    }

    public function view(AuthUser $authUser, ProjectMember $projectMember): bool
    {
        return $authUser->can('View:ProjectMember');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProjectMember');
    }

    public function update(AuthUser $authUser, ProjectMember $projectMember): bool
    {
        return $authUser->can('Update:ProjectMember');
    }

    public function delete(AuthUser $authUser, ProjectMember $projectMember): bool
    {
        return $authUser->can('Delete:ProjectMember');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProjectMember');
    }

    public function restore(AuthUser $authUser, ProjectMember $projectMember): bool
    {
        return $authUser->can('Restore:ProjectMember');
    }

    public function forceDelete(AuthUser $authUser, ProjectMember $projectMember): bool
    {
        return $authUser->can('ForceDelete:ProjectMember');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProjectMember');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProjectMember');
    }

    public function replicate(AuthUser $authUser, ProjectMember $projectMember): bool
    {
        return $authUser->can('Replicate:ProjectMember');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProjectMember');
    }
}
