<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum RolePermission: string
{
    case ViewAny = 'view_any_role';
    case View = 'view_role';
    case Create = 'create_role';
    case Update = 'update_role';
    case UpdateAny = 'update_any_role';
    case Delete = 'delete_role';
    case DeleteAny = 'delete_any_role';
}
