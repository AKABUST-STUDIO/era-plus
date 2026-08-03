<?php

namespace App\Filament\Project\Resources\TravelExpenses\Exporters;

use App\Models\Project\TravelExpense;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Support\Contracts\HasLabel;

class TravelExpenseExporter extends Exporter
{
    protected static ?string $model = TravelExpense::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('projectParticipant.participable.name')
                ->label(__('finance.fields.participant'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('projectParticipant.country.name')
                ->label(__('participant.fields.country'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('projectParticipant.sendingOrganization.name')
                ->label(__('participant.fields.sending_organization'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('travel_type')
                ->label(__('finance.fields.travel_type'))
                ->formatStateUsing(fn ($state): ?string => $state instanceof HasLabel ? $state->getLabel() : null),
            ExportColumn::make('transportation_type')
                ->label(__('finance.fields.transportation_type'))
                ->formatStateUsing(fn ($state): ?string => $state instanceof HasLabel ? $state->getLabel() : null),
            ExportColumn::make('from')
                ->label(__('finance.fields.from'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('to')
                ->label(__('finance.fields.to'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('date')
                ->label(__('finance.fields.date')),
            ExportColumn::make('cost')
                ->label(__('finance.fields.cost')),
            ExportColumn::make('currency')
                ->label(__('finance.fields.currency')),
            ExportColumn::make('cost_eur')
                ->label(__('finance.fields.cost_eur')),
            ExportColumn::make('proof_of_payment')
                ->label(__('finance.fields.proof_of_payment'))
                ->state(fn (TravelExpense $record): string => self::attached($record, TravelExpense::COLLECTION_PROOF_OF_PAYMENT)),
            ExportColumn::make('proof_of_journey')
                ->label(__('finance.fields.proof_of_journey'))
                ->state(fn (TravelExpense $record): string => self::attached($record, TravelExpense::COLLECTION_PROOF_OF_JOURNEY)),
        ];
    }

    private static function attached(TravelExpense $record, string $collection): string
    {
        return $record->hasMedia($collection)
            ? __('finance.export.attached')
            : __('finance.export.missing');
    }

    private static function sanitize(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $string = (string) $state;

        return in_array($string[0], ['=', '+', '-', '@', "\t", "\r"], strict: true)
            ? "'".$string
            : $string;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('finance.export.done_title', ['count' => number_format($export->successful_rows)]);

        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' '.__('finance.export.done_failed', ['count' => number_format($failed)]);
        }

        return $body;
    }
}
