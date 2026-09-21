<?php

namespace App\Filament\Project\Pages;

use App\Filament\Project\Widgets\DashboardCalendarWidget;
use App\Filament\Project\Widgets\ProjectActivityWidget;
use App\Filament\Project\Widgets\ProjectMembersWidget;
use App\Filament\Project\Widgets\ProjectParticipantsWidget;
use App\Filament\Project\Widgets\TravelExpensesWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\Widget;
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

    /**
     * @return array<class-string<Widget>>
     */
    public function getHeaderWidgets(): array
    {
        return [
            ProjectMembersWidget::class,
            ProjectParticipantsWidget::class,
            TravelExpensesWidget::class,
            ProjectActivityWidget::class,
            DashboardCalendarWidget::class,
        ];
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return ['default' => 1, 'md' => 2];
    }

    public function headerWidgets(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_START),
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema(fn (): array => $this->getWidgetsSchemaComponents([
                                ProjectMembersWidget::class,
                                ProjectParticipantsWidget::class,
                                TravelExpensesWidget::class,
                                ProjectActivityWidget::class,
                            ])),
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema(fn (): array => $this->getWidgetsSchemaComponents([
                                DashboardCalendarWidget::class,
                            ])),
                    ]),
                RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_END),
            ]);
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
