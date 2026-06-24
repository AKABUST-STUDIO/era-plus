<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class UserActivity extends Page
{
    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.user.pages.user-activity';

    public function getTitle(): string
    {
        return 'Activity';
    }
}
