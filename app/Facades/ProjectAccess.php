<?php

namespace App\Facades;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool can(User $user, string $ability, Project $project)
 * @method static bool administersOrganization(User $user, Organization $organization)
 * @method static bool administersProject(User $user, Project $project)
 *
 * @see \App\Services\ProjectAccess
 */
class ProjectAccess extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\ProjectAccess::class;
    }
}
