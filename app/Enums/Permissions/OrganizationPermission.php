<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum OrganizationPermission: string
{
    case Update = 'update_organization';
    case Delete = 'delete_organization';
}
