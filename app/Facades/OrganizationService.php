<?php

namespace App\Facades;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Organization|null current()
 * @method static Collection<int, Organization> organizationsFor(User $user)
 * @method static string urlFor(Organization $organization)
 * @method static void remember(Organization $organization)
 * @method static Organization|null selected()
 * @method static void forget()
 *
 * @see \App\Services\OrganizationService
 */
class OrganizationService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\OrganizationService::class;
    }
}
