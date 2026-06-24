<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class Activity extends Page
{
    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.user.pages.activity';

    public function getTitle(): string
    {
        return 'Activity';
    }
}
