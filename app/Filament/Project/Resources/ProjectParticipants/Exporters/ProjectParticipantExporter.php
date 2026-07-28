<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Exporters;

use App\Models\Project\ProjectParticipant;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProjectParticipantExporter extends Exporter
{
    protected static ?string $model = ProjectParticipant::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('participable.name')
                ->label(__('forms.common.name'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('participable.email')
                ->label(__('forms.common.email'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('participable.phone')
                ->label(__('participant.fields.phone'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('participable.date_of_birth')
                ->label(__('participant.fields.date_of_birth')),
            ExportColumn::make('country.name')
                ->label(__('participant.fields.country'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
            ExportColumn::make('sendingOrganization.name')
                ->label(__('participant.fields.sending_organization'))
                ->formatStateUsing(fn ($state): ?string => self::sanitize($state)),
        ];
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
        $body = __('participant.export.done_title', ['count' => number_format($export->successful_rows)]);

        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' '.__('participant.export.done_failed', ['count' => number_format($failed)]);
        }

        return $body;
    }
}
