<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use App\Models\ActivityLog;
use App\Models\Organization;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Activity extends Page implements HasTable
{
    use HasOrgSettingsBreadcrumbs;
    use InteractsWithTable;

    protected static ?string $slug = 'activity';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.organization.settings.pages.activity';

    public ?Organization $organization = null;

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.activity.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.activity.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActivityLog::query()
                ->where('organization_id', $this->organization->id)
                ->with(['user', 'project']))
            ->defaultSort('created_at', 'desc')
            ->defaultGroup(
                Group::make('created_at')
                    ->date()
                    ->getTitleFromRecordUsing(fn (ActivityLog $r): string => $r->created_at?->format('F Y') ?? '—')
            )
            ->columns([
                TextColumn::make('created_at')->label(__('forms.common.when'))->dateTime()->sortable(),
                TextColumn::make('user.name')->label(__('forms.common.user'))->placeholder('System')->searchable(),
                TextColumn::make('project.name')->label(__('forms.common.project'))->placeholder('—')->toggleable(),
                TextColumn::make('event')->label(__('forms.common.type'))->badge()->toggleable(),
                TextColumn::make('description')->label('Action')->searchable()->wrap(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('forms.common.user'))
                    ->relationship('user', 'name'),
                SelectFilter::make('project_id')
                    ->label(__('forms.common.project'))
                    ->relationship('project', 'name'),
                SelectFilter::make('event')
                    ->label('Event type')
                    ->options(fn (): array => ActivityLog::query()
                        ->where('organization_id', $this->organization->id)
                        ->whereNotNull('event')
                        ->distinct()
                        ->pluck('event', 'event')
                        ->all()),
                Filter::make('last_3_days')
                    ->label('Last 3 days')
                    ->query(fn (Builder $q): Builder => $q->where('created_at', '>=', now()->subDays(3))),
                Filter::make('last_30_days')
                    ->label('Last 30 days')
                    ->query(fn (Builder $q): Builder => $q->where('created_at', '>=', now()->subDays(30))),
            ]);
    }
}
