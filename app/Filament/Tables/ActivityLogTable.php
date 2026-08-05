<?php

namespace App\Filament\Tables;

use App\Filament\Tables\Filters\DateFilter;
use App\Filament\Tables\Filters\FilterGroup;
use App\Filament\Tables\Filters\SearchFilter;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ActivityLogTable
{
    public static function configure(Table $table, Builder $query): Table
    {
        return $table
            ->extraAttributes(['class' => 'fi-ta-activity'])
            ->query(fn (): Builder => $query->with(['causer', 'subject']))
            ->paginated(fn (HasTable $livewire): bool => ($livewire->getFilteredTableQuery()?->count() ?? 0) > 10)
            ->defaultSort('created_at', 'desc')
            ->defaultGroup(
                Group::make('month')
                    ->label(false)
                    ->getKeyFromRecordUsing(fn (ActivityLog $log): string => $log->created_at?->format('Y-m') ?? '')
                    ->getTitleFromRecordUsing(fn (ActivityLog $log): string => $log->created_at?->translatedFormat('F Y') ?? '—')
                    ->orderQueryUsing(fn (Builder $q, string $direction): Builder => $q->orderBy('created_at', $direction)),
            )
            ->columns([
                Split::make([
                    ImageColumn::make('causer_avatar')
                        ->state(fn (ActivityLog $record): string => self::avatarFor($record))
                        ->circular()
                        ->size(28)
                        ->grow(false),
                    TextColumn::make('description')
                        ->state(fn (ActivityLog $record): HtmlString => self::fluent($record))
                        ->html()
                        ->wrap()
                        ->searchable(),
                    TextColumn::make('created_at')
                        ->date('M j')
                        ->color('gray')
                        ->size('xs')
                        ->alignEnd()
                        ->grow(false),
                ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->searchable(false)
            ->hiddenFilterIndicators(true)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->columnManager(false)
            ->groupingSettingsHidden()
            ->filtersFormColumns(3)
            ->filters([
                SearchFilter::make()
                    ->columnStart(2),
                FilterGroup::make(
                    schema: [
                        Grid::make(2)->schema([
                            DateFilter::make('from', __('activity.filters.from'), 'lucide-calendar-arrow-up'),
                            DateFilter::make('until', __('activity.filters.until'), 'lucide-calendar-arrow-down'),
                        ]),
                    ],
                    query: fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)),
                    width: Width::Medium)
                    ->columnStart(3),
            ]);
    }

    private static function fluent(ActivityLog $record): HtmlString
    {
        $causer = $record->causer;
        $actor = $causer instanceof User && $causer->getKey() === auth()->id()
            ? __('activity.you')
            : ($causer?->name ?? __('activity.system'));

        $raw = (string) $record->description;
        $description = $raw;
        $properties = $record->properties instanceof Collection
            ? $record->properties->all()
            : (array) $record->properties;

        foreach ($properties as $key => $value) {
            if (is_scalar($value) && str_contains($description, ':'.$key)) {
                $description = str_replace(':'.$key, '<strong>'.e((string) $value).'</strong>', $description);
            }
        }

        if ($description === $raw) {
            $description = e($raw);
            $subject = $record->subject;

            if (filled($record->subject_type)) {
                $type = class_basename((string) $record->subject_type);
                $name = $subject?->name
                    ?? $subject?->title
                    ?? $subject?->slug
                    ?? ($record->subject_id !== null ? '#'.$record->subject_id : null);

                $description .= $name === null
                    ? ' <strong>'.e($type).'</strong>'
                    : ' '.e($type).' <strong>'.e((string) $name).'</strong>';
            }
        }

        return new HtmlString('<span class="fi-activity-subject">'.e($actor).'</span> '.$description);
    }

    private static function avatarFor(ActivityLog $record): string
    {
        $causer = $record->causer;

        if ($causer !== null && method_exists($causer, 'avatarUrl')) {
            return $causer->avatarUrl();
        }

        return 'https://ui-avatars.com/api/?name=System&size=64';
    }
}
