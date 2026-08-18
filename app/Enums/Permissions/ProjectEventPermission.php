<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum ProjectEventPermission: string
{
    case ViewAny = 'view_any_project_event';
    case View = 'view_project_event';
    case Create = 'create_project_event';
    case Update = 'update_project_event';
    case UpdateAny = 'update_any_project_event';
    case Delete = 'delete_project_event';
    case DeleteAny = 'delete_any_project_event';
}
