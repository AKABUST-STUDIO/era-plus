<?php

namespace App\Filament\User\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class UserAuthentication extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.user.pages.user-authentication';

    public function getTitle(): string
    {
        return 'Authentication';
    }
}
