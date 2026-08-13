<?php

namespace App\Facades;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection<int, Project> projectsFor(User $user, Organization $organization)
 * @method static int|null participationIdFor(User|null $user = null, Project|null $project = null)
 * @method static string urlFor(Project $project, string|null $page = null)
 * @method static string createUrlFor(Organization $organization)
 * @method static void remember(Project $project)
 * @method static Project|null selected()
 * @method static void forget()
 *
 * @see \App\Services\ProjectService
 */
class ProjectService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\ProjectService::class;
    }
}
