<?php

namespace App\Filament\Project\Pages;

use App\Models\ActivityLog;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectActivityLog extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationLabel(): string
    {
        return __('navigation.activity_log');
    }

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.project.pages.project-activity-log';

    public ?Project $project = null;

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Project, 404);
        $this->project = $tenant;
    }

    public function getTitle(): string
    {
        return __('forms.project.activity_log.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActivityLog::query()
                ->where('project_id', $this->project->id)
                ->with('user'))
            ->defaultSort('created_at', 'desc')
            ->defaultGroup(
                Group::make('created_at')
                    ->date()
                    ->getTitleFromRecordUsing(fn (ActivityLog $r): string => $r->created_at?->format('F Y') ?? '—')
            )
            ->columns([
                TextColumn::make('created_at')->label(__('forms.common.when'))->dateTime()->sortable(),
                TextColumn::make('user.name')->label(__('forms.common.user'))->placeholder(__('forms.project.activity_log.system'))->searchable(),
                TextColumn::make('event')->label(__('forms.common.type'))->badge()->toggleable(),
                TextColumn::make('description')->label(__('forms.project.activity_log.action'))->searchable()->wrap(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('forms.common.user'))
                    ->relationship('user', 'name'),
                Filter::make('last_3_days')
                    ->label(__('forms.project.activity_log.last_3_days'))
                    ->query(fn (Builder $q): Builder => $q->where('created_at', '>=', now()->subDays(3))),
                Filter::make('last_30_days')
                    ->label(__('forms.project.activity_log.last_30_days'))
                    ->query(fn (Builder $q): Builder => $q->where('created_at', '>=', now()->subDays(30))),
            ]);
    }
}
