<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class UserAuthentication extends Page
{
    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.user.pages.user-authentication';

    public function getTitle(): string
    {
        return 'Authentication';
    }
}
