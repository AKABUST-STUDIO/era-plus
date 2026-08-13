<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum InvoicePermission: string
{
    case View = 'view_invoice';
}
