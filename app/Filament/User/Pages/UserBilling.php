<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class UserBilling extends Page
{
    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.user.pages.user-billing';

    public function getTitle(): string
    {
        return 'Billing';
    }
}
