<?php

namespace App\Filament\User\Pages;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Providers\Filament\OrganizationPanelProvider;
use App\Providers\Filament\ProjectPanelProvider;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class Activity extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.user.pages.activity';

    public function getTitle(): string
    {
        return __('user.activity.title');
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getOrganizations(): Collection
    {
        /** @var Collection<int, Organization> $orgs */
        $orgs = Organization::query()
            ->whereHas('users', fn (Builder $query) => $query->whereKey(auth()->id()))
            ->orderBy('name')
            ->get();

        return $orgs;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        /** @var Collection<int, Project> $projects */
        $projects = Project::query()
            ->whereHas('users', fn (Builder $query) => $query->whereKey(auth()->id()))
            ->with('organization')
            ->orderBy('name')
            ->get();

        return $projects;
    }

    public function organizationActivityUrl(Organization $organization): ?string
    {
        try {
            return route('filament.'.OrganizationPanelProvider::PANEL_ID.'.pages.activity', [
                'tenant' => $organization->slug,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    public function projectActivityUrl(Project $project): ?string
    {
        try {
            return route('filament.'.ProjectPanelProvider::PANEL_ID.'.pages.project-activity-log', [
                'organization' => $project->organization?->slug,
                'tenant' => $project->slug,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    public function organizationDashboardUrl(Organization $organization): ?string
    {
        try {
            return route('filament.'.OrganizationPanelProvider::PANEL_ID.'.pages.dashboard', [
                'tenant' => $organization->slug,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActivityLog::query()
                ->causedBy(auth()->user())
                ->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('forms.common.when'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('event')
                    ->label(__('forms.common.type'))
                    ->badge(),
                TextColumn::make('description')
                    ->label(__('user.activity.action'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label(__('user.activity.subject'))
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? class_basename($state)
                        : '—')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('last_7_days')
                    ->label(__('user.activity.last_7_days'))
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
                Filter::make('last_30_days')
                    ->label(__('user.activity.last_30_days'))
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
            ]);
    }
}
