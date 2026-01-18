<?php

namespace App\Filament\Organization\Settings\Pages;

use Filament\Pages\Page;

class Activity extends Page
{
    protected static ?string $slug = 'activity';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.organization.settings.pages.work-in-progress';

    public static function getNavigationLabel(): string
    {
        return __('settings.activity.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.activity.title');
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            __('settings.breadcrumb'),
            __('settings.activity.navigation_label'),
        ];
    }
}
