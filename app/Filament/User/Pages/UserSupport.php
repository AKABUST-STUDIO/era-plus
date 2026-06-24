<?php

namespace App\Filament\User\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class UserSupport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.user.pages.user-support';

    public function getTitle(): string
    {
        return 'Support';
    }
}
