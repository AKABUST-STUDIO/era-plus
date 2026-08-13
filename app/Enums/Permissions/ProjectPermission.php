<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum ProjectPermission: string
{
    case ViewAny = 'view_any_project';
    case Create = 'create_project';
    case UpdateAny = 'update_any_project';
    case DeleteAny = 'delete_any_project';
}
