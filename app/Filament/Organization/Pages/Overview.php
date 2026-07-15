<?php

namespace App\Filament\Organization\Pages;

use App\Filament\Project\Widgets\ProjectCalendar;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Overview extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static BackedEnum|string|null $navigationIcon = null;

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            ProjectCalendar::class,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('filament-panels::pages/dashboard.title');
    }
}
