<?php

namespace App\Filament\Project\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;

class Overview extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static BackedEnum|string|null $navigationIcon = null;

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return 2;
    }

    public function headerWidgets(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_START),
                Grid::make($this->getHeaderWidgetsColumns())
                    ->extraAttributes(['class' => 'aaaaaa'])
                    ->schema(fn (): array => $this->cachedHeaderWidgetsSchemaComponents ??= $this->getWidgetsSchemaComponents($this->getHeaderWidgets())),
                RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_END),
            ])
            ->hidden(fn (): bool => empty($this->cachedHeaderWidgetsSchemaComponents ??= $this->getWidgetsSchemaComponents($this->getHeaderWidgets())));
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }
}
