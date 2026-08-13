<?php

namespace App\Filament\Organization\Pages;

use Filament\Pages\Page;

class Participants extends Page
{
    protected static ?string $slug = 'participants';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.organization.pages.select-project';

    public static function getNavigationLabel(): string
    {
        return __('navigation.participants');
    }

    public function getTitle(): string
    {
        return __('navigation.participants');
    }
}
