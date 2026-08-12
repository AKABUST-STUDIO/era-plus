<?php

namespace App\Filament\Project\Resources\ProjectEvents;

use App\Filament\Project\Resources\ProjectEvents\Pages\ListProjectEvents;
use App\Filament\Project\Resources\ProjectEvents\Schemas\ProjectEventForm;
use App\Models\Project\ProjectEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

class ProjectEventResource extends Resource
{
    protected static ?string $model = ProjectEvent::class;

    protected static ?string $slug = 'events';

    protected static ?string $recordTitleAttribute = 'title';

    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('events.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('events.navigation.singular');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectEventForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectEvents::route('/'),
        ];
    }
}
