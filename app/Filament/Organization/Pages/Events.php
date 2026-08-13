<?php

namespace App\Filament\Organization\Pages;

use Filament\Pages\Page;

class Events extends Page
{
    protected static ?string $slug = 'events';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.organization.pages.select-project';

    public static function getNavigationLabel(): string
    {
        return __('events.navigation.label');
    }

    public function getTitle(): string
    {
        return __('events.page.title');
    }
}
