<?php

namespace App\Filament\Project\Resources\TravelExpenses\Tables;

use App\Enums\Project\TransportationType;
use App\Enums\Project\TravelType;
use App\Facades\ProjectService;
use App\Filament\Project\Resources\TravelExpenses\Schemas\TravelExpenseForm;
use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use App\Filament\Tables\Filters\AttachmentToggle;
use App\Filament\Tables\Filters\ColumnManagerFilter;
use App\Filament\Tables\Filters\DateFilter;
use App\Filament\Tables\Filters\EnumSelect;
use App\Filament\Tables\Filters\FilterGroup;
use App\Filament\Tables\Filters\SearchFilter;
use App\Filament\Tables\Filters\SortFilter;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Number;
use Livewire\Component;

class TravelExpensesTable
{
    private const PARTICIPANT_SORT = 'projectParticipant.participable.name';

    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->defaultSort(fn (): string => TravelExpenseResource::canViewAllExpenses() ? self::PARTICIPANT_SORT : 'date', 'asc')
            ->defaultSortOptionLabel(__('finance.fields.date'))
            ->paginated(fn (HasTable $livewire): bool => ($livewire->getFilteredTableQuery()?->count() ?? 0) > 10)
            ->searchable(false)
            ->searchUsing(fn (Builder $query, string $search): Builder => $query->where(fn (Builder $query) => $query
                ->where('from', 'like', "%{$search}%")
                ->orWhere('to', 'like', "%{$search}%")
                ->orWhereHas('projectParticipant', fn (Builder $participant) => $participant
                    ->whereHasMorph('participable', '*', fn (Builder $morph) => $morph
                        ->where('name', 'like', "%{$search}%")))))
            ->modifyQueryUsing(function (Builder $query): Builder {
                if (! TravelExpenseResource::canViewAllExpenses()) {
                    $query->where('project_participant_id', ProjectService::participationIdFor() ?? 0);
                }

                return $query->with([
                    'projectParticipant' => fn ($participant) => $participant
                        ->with(['participable', 'country'])
                        ->withSum('travelExpenses', 'cost_eur'),
                    'countryLimit',
                    'media',
                ]);
            })
            ->columns([
                TextColumn::make('projectParticipant.participable.name')
                    ->label(__('finance.fields.participant'))
                    ->weight('semibold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $participations = (new ProjectParticipant)->getTable();

                        return $query->orderBy(
                            ProjectParticipant::query()
                                ->selectRaw('coalesce(participants.name, users.name)')
                                ->leftJoin('participants', fn (JoinClause $join) => $join
                                    ->on('participants.id', '=', "{$participations}.participable_id")
                                    ->where("{$participations}.participable_type", '=', Participant::class))
                                ->leftJoin('users', fn (JoinClause $join) => $join
                                    ->on('users.id', '=', "{$participations}.participable_id")
                                    ->where("{$participations}.participable_type", '=', User::class))
                                ->whereColumn("{$participations}.id", 'travel_expenses.project_participant_id')
                                ->limit(1),
                            $direction,
                        );
                    })
                    ->toggleable()
                    ->visible(fn (): bool => TravelExpenseResource::canViewAllExpenses()),
                ViewColumn::make('travel_type')
                    ->label(__('finance.fields.travel_type'))
                    ->view('filament.finance.travel-type')
                    ->sortable()
                    ->toggleable(),
                ViewColumn::make('from')
                    ->label(__('finance.fields.route'))
                    ->view('filament.finance.transport')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('date')
                    ->label(__('finance.fields.date'))
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->alignEnd(),
                TextColumn::make('cost_eur')
                    ->label(__('finance.fields.cost'))
                    ->money('EUR')
                    ->weight('semibold')
                    ->sortable()
                    ->tooltip(fn (TravelExpense $record): string => __('finance.fields.paid_as', [
                        'amount' => Number::currency((float) $record->cost, $record->currency),
                    ]))
                    ->summarize(Sum::make()->money('EUR'))
                    ->toggleable()
                    ->alignCenter()
                    ->description(fn (TravelExpense $record): ?Htmlable => TravelExpenseResource::canManageCountryLimits()
                        ? view('filament.finance.reimbursement', ['expense' => $record])
                        : null),
                IconColumn::make('proof_of_payment')
                    ->label(__('finance.fields.proof_of_payment'))
                    ->state(fn (TravelExpense $record): bool => $record->hasMedia(TravelExpense::COLLECTION_PROOF_OF_PAYMENT))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                IconColumn::make('proof_of_journey')
                    ->label(__('finance.fields.proof_of_journey'))
                    ->state(fn (TravelExpense $record): bool => $record->hasMedia(TravelExpense::COLLECTION_PROOF_OF_JOURNEY))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->groups([
                Group::make('projectParticipant.country.name')
                    ->label(__('participant.fields.country'))
                    ->getTitleFromRecordUsing(function (TravelExpense $record): string {
                        $country = $record->projectParticipant?->country;

                        if ($country === null) {
                            return __('finance.review.missing');
                        }

                        return trim(country_flag_emoji($country->iso2).' '.$country->name);
                    })
                    ->titlePrefixedWithLabel(false)
                    ->collapsible(),
            ])
            ->defaultGroup(fn (Component $livewire): ?string => ($livewire->activeTab ?? 'all') === 'all'
                && TravelExpenseResource::canViewAllExpenses()
                    ? 'projectParticipant.country.name'
                    : null)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->hiddenFilterIndicators(true)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->columnManager(false)
            ->groupingSettingsHidden()
            ->filtersFormColumns(6)
            ->filters([
                SortFilter::make(
                    TravelExpenseResource::canViewAllExpenses()
                        ? [
                            self::PARTICIPANT_SORT => __('finance.fields.participant'),
                            'date' => __('finance.sort.date'),
                            'cost_eur' => __('finance.sort.cost_eur'),
                        ]
                        : [
                            'date' => __('finance.sort.date'),
                            'cost_eur' => __('finance.sort.cost_eur'),
                        ],
                    __('finance.filters.sort'),
                    default: TravelExpenseResource::canViewAllExpenses() ? self::PARTICIPANT_SORT : 'date',
                )
                    ->columnStart(2),
                SearchFilter::make(__('finance.filters.search'))
                    ->columnStart(3)
                    ->columnSpan(2),
                FilterGroup::make([
                    EnumSelect::make('travel_type', TravelType::class, __('finance.filters.any_travel_type'), 'lucide-plane-takeoff'),
                    EnumSelect::make('transportation_type', TransportationType::class, __('finance.filters.any_transportation_type'), 'lucide-route'),
                    Grid::make(2)->schema([
                        DateFilter::make('date_from', __('finance.filters.date_from'), 'lucide-calendar-arrow-up'),
                        DateFilter::make('date_until', __('finance.filters.date_until'), 'lucide-calendar-arrow-down'),
                    ]),
                    AttachmentToggle::make('proof_of_payment', __('finance.filters.proof_of_payment_attached')),
                    AttachmentToggle::make('proof_of_journey', __('finance.filters.proof_of_journey_attached')),
                ], fn (Builder $query, array $data): Builder => $query
                    ->when(EnumSelect::applies($data['travel_type'] ?? null), fn (Builder $query): Builder => $query->where('travel_type', $data['travel_type']))
                    ->when(EnumSelect::applies($data['transportation_type'] ?? null), fn (Builder $query): Builder => $query->where('transportation_type', $data['transportation_type']))
                    ->when($data['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('date', '>=', $date))
                    ->when($data['date_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('date', '<=', $date))
                    ->when($data['proof_of_payment'] ?? false, fn (Builder $query): Builder => AttachmentToggle::apply($query, TravelExpense::COLLECTION_PROOF_OF_PAYMENT))
                    ->when($data['proof_of_journey'] ?? false, fn (Builder $query): Builder => AttachmentToggle::apply($query, TravelExpense::COLLECTION_PROOF_OF_JOURNEY)),
                    width: Width::Medium)
                    ->columnStart(5),
                ColumnManagerFilter::make()
                    ->columnStart(6),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->modalIcon('lucide-receipt-euro')
                        ->modalHeading(false)
                        ->modalWidth(Width::Large)
                        ->modalCloseButton(false)
                        ->modalCancelAction(false)
                        ->modalFooterActionsAlignment(Alignment::End)
                        ->modalSubmitActionLabel(__('finance.edit.submit'))
                        ->steps(TravelExpenseForm::steps())
                        ->modifyWizardUsing(fn (Wizard $wizard): Wizard => $wizard->hiddenHeader())
                        ->mutateRecordDataUsing(function (array $data, TravelExpense $record): array {
                            $participable = $record->projectParticipant?->participable;

                            if ($participable !== null) {
                                $data['participable_id'] = TravelExpenseForm::refFor($participable);
                            }

                            return $data;
                        })
                        ->mutateDataUsing(function (array $data, TravelExpense $record): array {
                            $data['project_participant_id'] = TravelExpenseResource::canViewAllExpenses()
                                ? TravelExpenseForm::resolveParticipationId($data) ?? $record->project_participant_id
                                : $record->project_participant_id;

                            return TravelExpenseForm::stripParticipableInput($data);
                        }),
                    DeleteAction::make()
                        ->modalWidth(Width::Large)
                        ->modalCloseButton(false)
                        ->modalFooterActionsAlignment(Alignment::End),
                ]),
            ])
            ->emptyStateHeading(__('finance.empty.heading'))
            ->emptyStateDescription(__('finance.empty.description'))
            ->emptyStateIcon('lucide-receipt-euro');
    }
}
