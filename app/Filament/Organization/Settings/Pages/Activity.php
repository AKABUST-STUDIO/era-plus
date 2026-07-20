<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
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
                    ->getTitleFromRecordUsing(fn (ActivityLog $activityLog): string => $activityLog->created_at?->format('F Y') ?? '—')
            )
            ->columns([
                TextColumn::make('created_at')->label(__('forms.common.when'))->dateTime()->sortable(),
                TextColumn::make('user.name')->label(__('forms.common.user'))->placeholder(__('settings.activity.system'))->searchable(),
                TextColumn::make('project.name')->label(__('forms.common.project'))->placeholder('—')->toggleable(),
                TextColumn::make('event')->label(__('forms.common.type'))->badge()->toggleable(),
                TextColumn::make('description')->label(__('settings.activity.action'))->searchable()->wrap(),
            ])
            ->filters([
                SelectFilter::make('causer_id')
                    ->label(__('forms.common.user'))
                    ->options(fn (): array => User::query()
                        ->whereIn('id', ActivityLog::query()
                            ->where('organization_id', $this->organization->id)
                            ->where('causer_type', User::class)
                            ->whereNotNull('causer_id')
                            ->distinct()
                            ->pluck('causer_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('project_id')
                    ->label(__('forms.common.project'))
                    ->relationship('project', 'name'),
                SelectFilter::make('event')
                    ->label(__('settings.activity.filters.event_type'))
                    ->options(fn (): array => ActivityLog::query()
                        ->where('organization_id', $this->organization->id)
                        ->whereNotNull('event')
                        ->distinct()
                        ->pluck('event', 'event')
                        ->all()),
                Filter::make('last_3_days')
                    ->label(__('settings.activity.filters.last_3_days'))
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(3))),
                Filter::make('last_30_days')
                    ->label(__('settings.activity.filters.last_30_days'))
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
            ]);
    }
}
