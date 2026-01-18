<?php

namespace App\Filament\Organization\Settings\Pages;

use Filament\Pages\Page;

class Billing extends Page
{
    protected static ?string $slug = 'billing';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.organization.settings.pages.work-in-progress';

    public static function getNavigationLabel(): string
    {
        return __('settings.billing.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.billing.title');
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            __('settings.breadcrumb'),
            __('settings.billing.navigation_label'),
        ];
    }
}
