<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\Page;

class ListProjects extends Page
{
    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament-panels::pages.page';

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }
}
