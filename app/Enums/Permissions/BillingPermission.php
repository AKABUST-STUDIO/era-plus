<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum BillingPermission: string
{
    case View = 'view_billing';
    case Update = 'update_billing';
}
