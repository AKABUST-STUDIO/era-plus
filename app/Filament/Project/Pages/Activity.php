<?php

namespace App\Filament\Project\Pages;

use App\Filament\Tables\ActivityLogTable;
use App\Models\ActivityLog;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Activity extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'activity';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.project.pages.activity';

    public ?Project $project = null;

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->can('view', ActivityLog::class) ?? false;
    }

    public function mount(): void
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Project, 404);

        $this->project = $tenant;
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.activity_log');
    }

    public function getTitle(): string
    {
        return __('forms.project.activity_log.title');
    }

    public function table(Table $table): Table
    {
        return ActivityLogTable::configure(
            $table,
            ActivityLog::query()->where('project_id', $this->project->id),
        );
    }
}
