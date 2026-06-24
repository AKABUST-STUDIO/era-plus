<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class Invoices extends Page
{
    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.user.pages.invoices';

    public function getTitle(): string
    {
        return 'Invoices';
    }
}
