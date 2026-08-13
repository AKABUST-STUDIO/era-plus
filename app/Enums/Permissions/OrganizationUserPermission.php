<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum OrganizationUserPermission: string
{
    case ViewAny = 'view_any_organization_user';
    case View = 'view_organization_user';
    case Create = 'create_organization_user';
    case Update = 'update_organization_user';
    case UpdateAny = 'update_any_organization_user';
    case Delete = 'delete_organization_user';
    case DeleteAny = 'delete_any_organization_user';
}
