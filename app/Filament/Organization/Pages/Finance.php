<?php

namespace App\Filament\Organization\Pages;

use Filament\Pages\Page;

class Finance extends Page
{
    protected static ?string $slug = 'finance';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.organization.pages.select-project';

    public static function getNavigationLabel(): string
    {
        return __('navigation.finance');
    }

    public function getTitle(): string
    {
        return __('finance.title');
    }
}
