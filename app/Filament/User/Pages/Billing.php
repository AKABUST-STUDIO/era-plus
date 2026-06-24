<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class Billing extends Page
{
    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.user.pages.billing';

    public function getTitle(): string
    {
        return 'Billing';
    }
}
