<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum ParticipantPermission: string
{
    case ViewAny = 'view_any_participant';
    case Create = 'create_participant';
    case Update = 'update_participant';
    case UpdateAny = 'update_any_participant';
    case Delete = 'delete_participant';
    case DeleteAny = 'delete_any_participant';
}
