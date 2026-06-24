<?php

namespace App\Filament\User\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class UserInvoices extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.user.pages.user-invoices';

    public function getTitle(): string
    {
        return 'Invoices';
    }
}
