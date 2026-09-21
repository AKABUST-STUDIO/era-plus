<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Organization\Widgets\OrganizationActivityWidget;
use App\Filament\Organization\Widgets\OrganizationProjectsWidget;
use App\Filament\Organization\Widgets\OrganizationUsersWidget;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class ListProjects extends Page
{
    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament-panels::pages.page';

    public function getTitle(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            OrganizationUsersWidget::class,
            OrganizationActivityWidget::class,
            OrganizationProjectsWidget::class,
        ];
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
                                OrganizationUsersWidget::class,
                                OrganizationActivityWidget::class,
                            ])),
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema(fn (): array => $this->getWidgetsSchemaComponents([
                                OrganizationProjectsWidget::class,
                            ])),
                    ]),
                RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_END),
            ]);
    }
}
