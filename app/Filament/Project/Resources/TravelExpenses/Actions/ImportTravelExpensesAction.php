<?php

namespace App\Filament\Project\Resources\TravelExpenses\Actions;

use App\Enums\Project\TransportationType;
use App\Enums\Project\TravelType;
use App\Filament\Project\Resources\TravelExpenses\Components\CurrencySelect;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use App\Models\User;
use App\Support\ImportCell;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Nnjeim\World\Models\Country;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportTravelExpensesAction
{
    private const MAX_FILE_KB = 5120;

    private const MAX_ROWS = 500;

    public static function make(string $name = 'import'): Action
    {
        return Action::make($name)
            ->label(__('finance.actions.import'))
            ->icon('lucide-upload')
            ->modalIcon('lucide-upload')
            ->modalHeading(false)
            ->modalDescription(__('finance.import.description'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalCloseButton(false)
            ->modalWidth(Width::Large)
            ->schema([
                Wizard::make([
                    Step::make(__('finance.import.step_download'))
                        ->schema([
                            Section::make(__('finance.import.step_download'))
                                ->label(__('finance.import.step_download_body'))
                                ->icon('lucide-file-down')
                                ->contained(false)
                                ->components([
                                    SchemaActions::make([
                                        Action::make('downloadExample')
                                            ->label(__('finance.import.download_example'))
                                            ->icon('lucide-file-down')
                                            ->iconPosition(IconPosition::After)
                                            ->size(Size::Large)
                                            ->link()
                                            ->action(fn () => response()->download(
                                                resource_path('xlsx/rasmo_import_travel_expenses.xlsx'),
                                                'rasmo_import_travel_expenses.xlsx',
                                            )),
                                    ])->alignCenter(),
                                ]),
                        ]),
                    Step::make(__('finance.import.step_fill'))
                        ->schema([
                            Section::make(__('finance.import.step_fill'))
                                ->label(__('finance.import.step_fill_body'))
                                ->icon('lucide-file-pen-line')
                                ->contained(false)
                                ->components([
                                    View::make('filament.finance.import-example-gif'),
                                ]),
                        ]),
                    Step::make(__('finance.import.step_upload'))
                        ->schema([
                            Section::make(__('finance.import.step_upload'))
                                ->label(__('finance.import.step_upload_description'))
                                ->icon('lucide-upload')
                                ->contained(false)
                                ->components([
                                    FileUpload::make('file')
                                        ->hiddenLabel()
                                        ->label(__('finance.import.file_label'))
                                        ->acceptedFileTypes([
                                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                            'application/vnd.ms-excel',
                                        ])
                                        ->maxSize(self::MAX_FILE_KB)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set): void {
                                            if (! $state instanceof TemporaryUploadedFile) {
                                                $set('rows', []);

                                                return;
                                            }

                                            $sheets = Excel::toArray(new class {}, $state->getRealPath());
                                            $rows = $sheets[0] ?? [];
                                            array_shift($rows);

                                            $parsed = array_values(array_filter(
                                                array_map(fn (array $row): array => [
                                                    'participant_name' => ImportCell::clean($row[0] ?? null),
                                                    'country_name' => ImportCell::clean($row[1] ?? null),
                                                    'travel_type' => self::resolveTravelType($row[2] ?? null),
                                                    'transportation_type' => self::resolveTransportationType($row[3] ?? null),
                                                    'from' => ImportCell::clean($row[4] ?? null),
                                                    'to' => ImportCell::clean($row[5] ?? null),
                                                    'date' => self::normaliseDate($row[6] ?? null),
                                                    'cost' => self::normaliseDecimal($row[7] ?? null),
                                                    'currency' => self::normaliseCurrency($row[8] ?? null),
                                                    'cost_eur' => self::normaliseDecimal($row[9] ?? null),
                                                    '_matched_participation_id' => self::resolveParticipationId(
                                                        $row[0] ?? null,
                                                        $row[1] ?? null,
                                                    ),
                                                ], $rows),
                                                fn (array $row): bool => filled($row['participant_name'])
                                                    || filled($row['from'])
                                                    || filled($row['to'])
                                                    || filled($row['cost']),
                                            ));

                                            $originalCount = count($parsed);

                                            if ($originalCount > self::MAX_ROWS) {
                                                $parsed = array_slice($parsed, 0, self::MAX_ROWS);

                                                Notification::make()
                                                    ->title(__('finance.import.truncated_title'))
                                                    ->body(__('finance.import.truncated_body', [
                                                        'max' => self::MAX_ROWS,
                                                        'dropped' => $originalCount - self::MAX_ROWS,
                                                    ]))
                                                    ->warning()
                                                    ->send();
                                            }

                                            $set('rows', $parsed);
                                        }),
                                ]),
                        ]),
                    Step::make(__('finance.import.step_review'))
                        ->extraAttributes(['class' => 'participant-import-review-step'])
                        ->schema([
                            Section::make(__('finance.import.step_review'))
                                ->label(__('finance.import.step_review_description'))
                                ->icon('lucide-check-check')
                                ->contained(false)
                                ->components([
                                    Repeater::make('rows')
                                        ->hiddenLabel()
                                        ->default([])
                                        ->addable(false)
                                        ->reorderable(false)
                                        ->schema([
                                            Section::make(__('finance.sections.details'))
                                                ->icon('lucide-circle-user-round')
                                                ->contained(false)
                                                ->columns(4)
                                                ->components([
                                                    TextInput::make('participant_name')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.import.participant_placeholder'))
                                                        ->prefixIcon('lucide-circle-user-round')
                                                        ->required()
                                                        ->columnSpan(2)
                                                        ->live(debounce: 500)
                                                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                                            $set('_matched_participation_id', self::resolveParticipationId(
                                                                $state,
                                                                $get('country_name'),
                                                            ));
                                                        })
                                                        ->hint(fn (Get $get): ?string => blank($get('_matched_participation_id'))
                                                            ? __('finance.import.participant_missing')
                                                            : null)
                                                        ->hintColor('danger')
                                                        ->hintIcon(fn (Get $get): ?string => blank($get('_matched_participation_id'))
                                                            ? 'lucide-triangle-alert'
                                                            : null),
                                                    TextInput::make('country_name')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.import.country_placeholder'))
                                                        ->prefixIcon('lucide-map-pin-house')
                                                        ->required()
                                                        ->columnSpan(2)
                                                        ->live(debounce: 500)
                                                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                                            $set('_matched_participation_id', self::resolveParticipationId(
                                                                $get('participant_name'),
                                                                $state,
                                                            ));
                                                        }),
                                                ]),
                                            Section::make(__('finance.sections.travel'))
                                                ->icon('lucide-route')
                                                ->contained(false)
                                                ->columns(4)
                                                ->components([
                                                    Select::make('travel_type')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.import.travel_type_placeholder'))
                                                        ->options(TravelType::class)
                                                        ->required()
                                                        ->columnSpan(2),
                                                    Select::make('transportation_type')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.import.transportation_type_placeholder'))
                                                        ->options(TransportationType::class)
                                                        ->required()
                                                        ->columnSpan(2),
                                                    TextInput::make('from')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.fields.from'))
                                                        ->prefixIcon('lucide-circle-dot')
                                                        ->required()
                                                        ->columnSpan(2)
                                                        ->maxLength(255),
                                                    TextInput::make('to')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.fields.to'))
                                                        ->prefixIcon('lucide-map-pin')
                                                        ->required()
                                                        ->columnSpan(2)
                                                        ->maxLength(255),
                                                    DatePicker::make('date')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.fields.date'))
                                                        ->prefixIcon('lucide-calendar')
                                                        ->native(false)
                                                        ->required()
                                                        ->columnSpanFull(),
                                                ]),
                                            Section::make(__('finance.sections.cost'))
                                                ->icon('lucide-receipt-euro')
                                                ->contained(false)
                                                ->columns(4)
                                                ->components([
                                                    TextInput::make('cost')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.fields.cost'))
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->step(0.01)
                                                        ->required()
                                                        ->columnSpan(2),
                                                    CurrencySelect::make(),
                                                    TextInput::make('cost_eur')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('finance.fields.cost_eur'))
                                                        ->prefix(__('forms.common.currency_prefix'))
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->step(0.01)
                                                        ->required()
                                                        ->columnSpanFull(),
                                                ]),
                                        ]),
                                    Text::make(__('finance.import.step_review_empty'))
                                        ->visible(fn (Get $get): bool => blank($get('rows'))),
                                ]),
                        ]),
                ])
                    ->hiddenHeader()
                    ->skippable(false)
                    ->contained(false)
                    ->nextAction(fn (Action $action) => $action->label(__('finance.import.next')))
                    ->previousAction(fn (Action $action) => $action->label(__('finance.import.back')))
                    ->submitAction(view('filament.finance.import-wizard-submit')),
            ])
            ->action(function (array $data): void {
                $project = Filament::getTenant();

                if (! $project instanceof Project) {
                    return;
                }

                $rows = $data['rows'] ?? [];

                if (empty($rows)) {
                    Notification::make()
                        ->title(__('finance.import.empty_title'))
                        ->body(__('finance.import.empty_body'))
                        ->warning()
                        ->send();

                    return;
                }

                $created = 0;
                $skipped = [];

                DB::transaction(function () use ($rows, &$created, &$skipped): void {
                    foreach ($rows as $index => $row) {
                        $participationId = $row['_matched_participation_id']
                            ?? self::resolveParticipationId(
                                $row['participant_name'] ?? null,
                                $row['country_name'] ?? null,
                            );

                        if (blank($participationId)) {
                            $skipped[] = ($index + 1).': '.($row['participant_name'] ?? '?');

                            continue;
                        }

                        TravelExpense::create([
                            'project_participant_id' => $participationId,
                            'travel_type' => $row['travel_type'] ?? null,
                            'transportation_type' => $row['transportation_type'] ?? null,
                            'from' => ImportCell::clean($row['from'] ?? null),
                            'to' => ImportCell::clean($row['to'] ?? null),
                            'date' => $row['date'] ?? null,
                            'cost' => $row['cost'] ?? null,
                            'currency' => $row['currency'] ?? null,
                            'cost_eur' => $row['cost_eur'] ?? null,
                        ]);

                        $created++;
                    }
                });

                Notification::make()
                    ->title(__('finance.import.done_title', ['count' => $created]))
                    ->body(empty($skipped)
                        ? null
                        : __('finance.import.done_skipped', ['rows' => implode(', ', $skipped)]))
                    ->success()
                    ->send();
            });
    }

    private static function resolveParticipationId(mixed $name, mixed $country): ?int
    {
        $cleanName = ImportCell::clean($name);
        $cleanCountry = ImportCell::clean($country);

        if ($cleanName === null || $cleanCountry === null) {
            return null;
        }

        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return null;
        }

        $countryId = Country::query()->where('name', $cleanCountry)->value('id');

        if ($countryId === null) {
            return null;
        }

        $userIds = User::query()->where('name', $cleanName)->pluck('id')->all();
        $participantIds = Participant::query()->where('name', $cleanName)->pluck('id')->all();

        if (empty($userIds) && empty($participantIds)) {
            return null;
        }

        $id = ProjectParticipant::query()
            ->where('project_id', $project->id)
            ->where('country_id', (int) $countryId)
            ->where(function (Builder $query) use ($userIds, $participantIds): void {
                if (! empty($userIds)) {
                    $query->orWhere(fn (Builder $q) => $q
                        ->where('participable_type', User::class)
                        ->whereIn('participable_id', $userIds));
                }

                if (! empty($participantIds)) {
                    $query->orWhere(fn (Builder $q) => $q
                        ->where('participable_type', Participant::class)
                        ->whereIn('participable_id', $participantIds));
                }
            })
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private static function resolveTravelType(mixed $value): ?string
    {
        $raw = ImportCell::clean($value);

        if ($raw === null) {
            return null;
        }

        $needle = mb_strtolower($raw);

        foreach (TravelType::cases() as $case) {
            if ($case->value === $needle || mb_strtolower($case->getLabel()) === $needle) {
                return $case->value;
            }
        }

        return null;
    }

    private static function resolveTransportationType(mixed $value): ?string
    {
        $raw = ImportCell::clean($value);

        if ($raw === null) {
            return null;
        }

        $needle = mb_strtolower($raw);

        foreach (TransportationType::cases() as $case) {
            if ($case->value === $needle || mb_strtolower($case->getLabel()) === $needle) {
                return $case->value;
            }
        }

        return null;
    }

    private static function normaliseCurrency(mixed $value): ?string
    {
        $raw = ImportCell::clean($value);

        return $raw === null ? null : mb_strtoupper($raw);
    }

    private static function normaliseDecimal(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $raw = str_replace([' ', ','], ['', '.'], trim((string) $value));

        return is_numeric($raw) ? number_format((float) $raw, 2, '.', '') : null;
    }

    private static function normaliseDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $raw = trim((string) $value);

        $formats = [
            'Y-m-d', 'Y/m/d', 'Y.m.d',
            'd-m-Y', 'd/m/Y', 'd.m.Y',
            'm-d-Y', 'm/d/Y', 'm.d.Y',
            'd-m-y', 'd/m/y', 'd.m.y',
            'j F Y', 'j M Y', 'F j, Y', 'M j, Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat($format, $raw);
            } catch (\Throwable) {
                continue;
            }

            if ($parsed !== false && $parsed->format($format) === $raw) {
                return $parsed->toDateString();
            }
        }

        try {
            return CarbonImmutable::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
