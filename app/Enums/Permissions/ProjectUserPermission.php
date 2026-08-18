<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum ProjectUserPermission: string
{
    case ViewAny = 'view_any_project_user';
    case View = 'view_project_user';
    case Create = 'create_project_user';
    case Update = 'update_project_user';
    case UpdateAny = 'update_any_project_user';
    case Delete = 'delete_project_user';
    case DeleteAny = 'delete_any_project_user';
}
