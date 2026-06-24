<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use Filament\Pages\Page;

class Notifications extends Page
{
    use HasOrgSettingsBreadcrumbs;

    protected static ?string $slug = 'notifications';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.organization.settings.pages.work-in-progress';

    public static function getNavigationLabel(): string
    {
        return __('settings.notifications.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.notifications.title');
    }
}
