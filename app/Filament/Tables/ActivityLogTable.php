<?php

namespace App\Filament\Tables;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
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
            ->query(fn (): Builder => $query->with(['causer', 'subject']))
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
            ->deferFilters(false)
            ->filters([
                Filter::make('created_between')
                    ->form([
                        DatePicker::make('from')->label(__('activity.filters.from')),
                        DatePicker::make('until')->label(__('activity.filters.until')),
                    ])
                    ->query(fn (Builder $q, array $data): Builder => $q
                        ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($from = $data['from'] ?? null) {
                            $indicators[] = __('activity.filters.from').': '.$from;
                        }

                        if ($until = $data['until'] ?? null) {
                            $indicators[] = __('activity.filters.until').': '.$until;
                        }

                        return $indicators;
                    }),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    private static function fluent(ActivityLog $record): HtmlString
    {
        $causer = $record->causer;
        $isSelf = $causer !== null
            && $causer::class === User::class
            && auth()->check()
            && $causer->getKey() === auth()->id();

        $subject = $isSelf
            ? __('activity.you')
            : ($causer->name ?? __('activity.system'));

        $description = self::interpolate($record);

        return new HtmlString(
            '<span class="fi-activity-subject">'.e($subject).'</span> '.$description,
        );
    }

    private static function interpolate(ActivityLog $record): string
    {
        $rawDescription = (string) $record->description;
        $description = $rawDescription;
        $properties = $record->properties instanceof Collection
            ? $record->properties->all()
            : (array) $record->properties;

        $hasPlaceholder = false;

        foreach ($properties as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            if (str_contains($description, ':'.$key)) {
                $hasPlaceholder = true;
                $description = str_replace(':'.$key, '<strong>'.e((string) $value).'</strong>', $description);
            }
        }

        if ($hasPlaceholder) {
            return $description;
        }

        $subject = self::subjectLabel($record);

        if ($subject !== null) {
            return e($rawDescription).' '.$subject;
        }

        return e($rawDescription);
    }

    private static function subjectLabel(ActivityLog $record): ?string
    {
        if (blank($record->subject_type)) {
            return null;
        }

        $type = class_basename((string) $record->subject_type);
        $model = $record->subject;

        $name = $model?->name
            ?? $model?->title
            ?? $model?->slug
            ?? ($record->subject_id !== null ? '#'.$record->subject_id : null);

        if ($name === null) {
            return '<strong>'.e($type).'</strong>';
        }

        return e($type).' <strong>'.e((string) $name).'</strong>';
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
