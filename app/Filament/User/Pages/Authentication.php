<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class Authentication extends Page
{
    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.user.pages.authentication';

    public function getTitle(): string
    {
        return 'Authentication';
    }
}
