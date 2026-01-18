<?php

namespace App\Filament\Organization\Settings\Pages;

use Filament\Pages\Page;

class Notifications extends Page
{
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

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            __('settings.breadcrumb'),
            __('settings.notifications.navigation_label'),
        ];
    }
}
