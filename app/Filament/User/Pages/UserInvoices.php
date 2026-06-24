<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class UserInvoices extends Page
{
    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.user.pages.user-invoices';

    public function getTitle(): string
    {
        return 'Invoices';
    }
}
