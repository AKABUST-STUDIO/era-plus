<?php

declare(strict_types=1);

namespace App\Enums\Permissions;

enum TravelExpensePermission: string
{
    case ViewAny = 'view_any_travel_expense';
    case View = 'view_travel_expense';
    case Create = 'create_travel_expense';
    case Update = 'update_travel_expense';
    case UpdateAny = 'update_any_travel_expense';
    case Delete = 'delete_travel_expense';
    case DeleteAny = 'delete_any_travel_expense';
    case Import = 'import_travel_expense';
    case Export = 'export_travel_expense';
    case CountryLimits = 'country_limits_travel_expense';
}
