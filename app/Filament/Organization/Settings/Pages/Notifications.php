<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Filament\Concerns\GatedByOrganizationPermission;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use Filament\Pages\Page;

class Notifications extends Page
{
    use GatedByOrganizationPermission;
    use HasOrgSettingsBreadcrumbs;

    protected static ?string $slug = 'notifications';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.organization.settings.pages.work-in-progress';

    protected static function organizationPermission(): string
    {
        return 'view_any_setting';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.notifications.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.notifications.title');
    }
}
